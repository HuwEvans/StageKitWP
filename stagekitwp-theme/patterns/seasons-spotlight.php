<?php
/**
 * Title: Seasons Spotlight
 * Slug: stagekitwp-theme/seasons-spotlight
 * Categories: stagekitwp-theme, query
 * Block Types: core/query
 * Post Types: page, post
 * Description: Spotlight layout for a single season with large image, dates, and summary.
 */
?>
<!-- wp:query {"queryId":12,"query":{"perPage":1,"pages":0,"offset":0,"postType":"season","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"displayLayout":{"type":"list"},"align":"wide"} -->
<div class="wp-block-query alignwide"><!-- wp:post-template -->
<!-- wp:group {"style":{"spacing":{"blockGap":"0.9rem","padding":{"top":"0","right":"0","bottom":"0","left":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:post-featured-image {"isLink":true,"height":"520px","style":{"border":{"radius":"16px"}}} /-->

<!-- wp:post-terms {"term":"category","fontSize":"small"} /-->

<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"2.2rem","lineHeight":"1.1"}}} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"0.6rem"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:post-date {"fontSize":"small"} /-->

<!-- wp:post-author {"showAvatar":false,"fontSize":"small"} /--></div>
<!-- /wp:group -->

<!-- wp:post-excerpt {"moreText":"Explore season","excerptLength":55} /--></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->