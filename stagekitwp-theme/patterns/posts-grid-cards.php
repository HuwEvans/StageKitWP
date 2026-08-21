<?php
/**
 * Title: Posts Grid Cards
 * Slug: stagekitwp-theme/posts-grid-cards
 * Categories: stagekitwp-theme, query
 * Block Types: core/query
 * Post Types: page, post
 * Description: Three-column post grid with featured image, categories, title, and excerpt.
 */
?>
<!-- wp:query {"queryId":1,"query":{"perPage":6,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"displayLayout":{"type":"grid","columns":3},"align":"wide"} -->
<div class="wp-block-query alignwide"><!-- wp:post-template -->
<!-- wp:group {"style":{"spacing":{"blockGap":"0.65rem","padding":{"top":"0","right":"0","bottom":"1rem","left":"0"},"margin":{"top":"0","bottom":"0"}},"border":{"radius":"12px"},"shadow":"var:preset|shadow|natural"},"backgroundColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-base-background-color has-background" style="border-radius:12px;margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:1rem;padding-left:0"><!-- wp:post-featured-image {"isLink":true,"height":"220px","style":{"border":{"radius":{"topLeft":"12px","topRight":"12px","bottomLeft":"0px","bottomRight":"0px"}}}} /-->

<!-- wp:group {"style":{"spacing":{"padding":{"right":"1rem","left":"1rem"},"blockGap":"0.4rem"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-right:1rem;padding-left:1rem"><!-- wp:post-terms {"term":"category","fontSize":"small"} /-->

<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"1.2rem","lineHeight":"1.25"}}} /-->

<!-- wp:post-excerpt {"moreText":"Read article","excerptLength":18} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->