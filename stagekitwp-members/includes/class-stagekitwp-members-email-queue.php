<?php
/**
 * Scheduled/batched email queue.
 *
 * Jobs are stored in the stagekitwp_members_email_queue option as an array of records:
 *   [ id, email, subject, message, send_time, attempts, status, last_error ]
 *
 * A cron event (every minute) processes due jobs in capped batches so a large
 * bulk send never blocks a single cron run. Failed sends are retried up to
 * MAX_ATTEMPTS, then marked 'failed' and kept for visibility in the admin
 * queue viewer.
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Email_Queue {

    const OPTION_KEY   = 'stagekitwp_members_email_queue';
    const BATCH_SIZE   = 25;   // max sends per cron run
    const MAX_ATTEMPTS = 3;    // retry ceiling before marking failed

    const STATUS_PENDING = 'pending';
    const STATUS_SENT    = 'sent';
    const STATUS_FAILED  = 'failed';

    public static function init() {
        add_filter('cron_schedules', [__CLASS__, 'add_cron_interval']);
        add_action('init', [__CLASS__, 'register_cron']);
        add_action('stagekitwp_members_process_queue', [__CLASS__, 'process_queue']);
    }

    /**
     * 'minute' is not a WordPress default interval.
     */
    public static function add_cron_interval($schedules) {
        if (!isset($schedules['stagekitwp_members_minute'])) {
            $schedules['stagekitwp_members_minute'] = [
                'interval' => 60,
                'display'  => __('Every Minute (TM Members)', 'stagekitwp-members-area'),
            ];
        }
        return $schedules;
    }

    public static function register_cron() {
        if (!wp_next_scheduled('stagekitwp_members_process_queue')) {
            wp_schedule_event(time(), 'stagekitwp_members_minute', 'stagekitwp_members_process_queue');
        }
    }

    public static function clear_cron() {
        $timestamp = wp_next_scheduled('stagekitwp_members_process_queue');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'stagekitwp_members_process_queue');
        }
    }

    /**
     * Read/write helpers.
     */
    public static function get_queue() {
        return (array) get_option(self::OPTION_KEY, []);
    }

    private static function save_queue($queue) {
        update_option(self::OPTION_KEY, array_values($queue));
    }

    /**
     * Add a job. $time may be a unix timestamp or a strtotime()-able string.
     * Returns the job id.
     */
    public static function add_to_queue($email, $subject, $message, $time = null) {

        $send_time = self::normalize_time($time);

        $queue = self::get_queue();

        $job = [
            'id'         => self::next_id($queue),
            'email'      => sanitize_email($email),
            'subject'    => $subject,
            'message'    => $message,
            'send_time'  => $send_time,
            'attempts'   => 0,
            'status'     => self::STATUS_PENDING,
            'last_error' => '',
            'created_at' => time(),
        ];

        $queue[] = $job;
        self::save_queue($queue);

        return $job['id'];
    }

    /**
     * Accepts null, an int timestamp, or a datetime string (from the
     * dashboard's datetime-local field). Falls back to "now".
     */
    private static function normalize_time($time) {
        if (empty($time)) {
            return time();
        }
        if (is_numeric($time)) {
            return (int) $time;
        }
        $parsed = strtotime($time);
        return $parsed ?: time();
    }

    private static function next_id($queue) {
        $max = 0;
        foreach ($queue as $job) {
            if (isset($job['id']) && $job['id'] > $max) {
                $max = $job['id'];
            }
        }
        return $max + 1;
    }

    /**
     * Process due jobs in a capped batch. Retries failures up to MAX_ATTEMPTS.
     */
    public static function process_queue() {

        $queue = self::get_queue();
        if (empty($queue)) {
            return;
        }

        $now       = time();
        $processed = 0;

        foreach ($queue as $index => $job) {

            // Backward-compat: older jobs may lack the new fields.
            $job = self::hydrate($job);

            if ($processed >= self::BATCH_SIZE) {
                break; // leave the rest for the next run
            }

            // Skip jobs that are not due or already terminal.
            if ($job['status'] === self::STATUS_SENT || $job['status'] === self::STATUS_FAILED) {
                $queue[$index] = $job;
                continue;
            }
            if ($job['send_time'] > $now) {
                $queue[$index] = $job;
                continue;
            }

            $job['attempts']++;

            $sent = STAGEKITWP_MEMBERS_Health::send_email($job['email'], $job['subject'], $job['message']);

            if ($sent) {
                $job['status']     = self::STATUS_SENT;
                $job['last_error'] = '';
            } elseif ($job['attempts'] >= self::MAX_ATTEMPTS) {
                $job['status']     = self::STATUS_FAILED;
                $job['last_error'] = 'Send failed after ' . self::MAX_ATTEMPTS . ' attempts.';
            } else {
                // Keep pending; will retry next run.
                $job['last_error'] = 'Send failed, will retry.';
            }

            $queue[$index] = $job;
            $processed++;
        }

        // Prune jobs that succeeded and are older than a day to keep the
        // option small, but keep failed/pending for visibility.
        $queue = self::prune($queue);

        self::save_queue($queue);
    }

    /**
     * Ensure a job has all expected keys.
     */
    private static function hydrate($job) {
        return array_merge([
            'id'         => 0,
            'email'      => '',
            'subject'    => '',
            'message'    => '',
            'send_time'  => time(),
            'attempts'   => 0,
            'status'     => self::STATUS_PENDING,
            'last_error' => '',
            'created_at' => time(),
        ], (array) $job);
    }

    /**
     * Drop sent jobs older than 24h.
     */
    private static function prune($queue) {
        $cutoff = time() - DAY_IN_SECONDS;
        return array_filter($queue, function ($job) use ($cutoff) {
            if (($job['status'] ?? '') === self::STATUS_SENT && ($job['created_at'] ?? 0) < $cutoff) {
                return false;
            }
            return true;
        });
    }

    /**
     * Counts by status for the admin viewer.
     */
    public static function counts() {
        $counts = ['pending' => 0, 'sent' => 0, 'failed' => 0, 'total' => 0];
        foreach (self::get_queue() as $job) {
            $job = self::hydrate($job);
            $counts['total']++;
            if (isset($counts[$job['status']])) {
                $counts[$job['status']]++;
            }
        }
        return $counts;
    }

    /**
     * Remove a single job by id.
     */
    public static function delete_job($id) {
        $queue = array_filter(self::get_queue(), function ($job) use ($id) {
            return (int) ($job['id'] ?? 0) !== (int) $id;
        });
        self::save_queue($queue);
    }

    /**
     * Requeue a failed job (reset status/attempts, send now).
     */
    public static function retry_job($id) {
        $queue = self::get_queue();
        foreach ($queue as $i => $job) {
            if ((int) ($job['id'] ?? 0) === (int) $id) {
                $queue[$i] = self::hydrate($job);
                $queue[$i]['status']    = self::STATUS_PENDING;
                $queue[$i]['attempts']  = 0;
                $queue[$i]['send_time'] = time();
                $queue[$i]['last_error'] = '';
            }
        }
        self::save_queue($queue);
    }

    /**
     * Clear every job.
     */
    public static function clear_queue() {
        self::save_queue([]);
    }
}
