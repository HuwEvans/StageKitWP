<?php
/**
 * Title: Posts Spotlight Stack
 * Slug: stagekitwp-theme/posts-spotlight-stack
 * Categories: stagekitwp-theme, query
 * Block Types: core/query
 * Post Types: page, post
 * Description: One featured spotlight post followed by a compact list of recent stories.
 */
?>
<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:query {"queryId":2,"query":{"perPage":1,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"displayLayout":{"type":"list"}} -->
<div class="wp-block-query"><!-- wp:post-template -->
<!-- wp:group {"style":{"spacing":{"blockGap":"0.7rem"},"border":{"radius":"14px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="border-radius:14px"><!-- wp:post-featured-image {"isLink":true,"height":"420px","style":{"border":{"radius":"14px"}}} /-->

<!-- wp:post-terms {"term":"category","fontSize":"small"} /-->

<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"2rem","lineHeight":"1.15"}}} /-->

<!-- wp:post-excerpt {"moreText":"Continue reading","excerptLength":30} /--></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->

<!-- wp:separator {"style":{"spacing":{"margin":{"top":"1.5rem","bottom":"1.5rem"}}}} -->
<hr class="wp-block-separator" style="margin-top:1.5rem;margin-bottom:1.5rem"/>
<!-- /wp:separator -->

<!-- wp:query {"queryId":3,"query":{"perPage":4,"pages":0,"offset":1,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"displayLayout":{"type":"list"}} -->
<div class="wp-block-query"><!-- wp:post-template -->
<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"margin":{"bottom":"1rem"},"blockGap":{"left":"1rem"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center" style="margin-bottom:1rem"><!-- wp:column {"verticalAlignment":"center","width":"120px"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:120px"><!-- wp:post-featured-image {"isLink":true,"height":"90px","style":{"border":{"radius":"10px"}}} /--></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:post-title {"isLink":true,"level":3,"style":{"typography":{"fontSize":"1.1rem","lineHeight":"1.3"}}} /-->

<!-- wp:post-date {"fontSize":"small"} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group -->