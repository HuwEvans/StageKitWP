<?php
/**
 * Title: Posts Text Over Graphic
 * Slug: stagekitwp-theme/posts-text-over-graphic
 * Categories: stagekitwp-theme, query
 * Block Types: core/query
 * Post Types: page, post
 * Description: Featured-image covers with post title and excerpt overlaid on top of each graphic.
 */
?>
<!-- wp:query {"queryId":6,"query":{"perPage":4,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"displayLayout":{"type":"grid","columns":2},"align":"wide"} -->
<div class="wp-block-query alignwide"><!-- wp:post-template -->
<!-- wp:cover {"useFeaturedImage":true,"dimRatio":45,"minHeight":340,"isDark":true,"style":{"border":{"radius":"14px"},"spacing":{"padding":{"top":"1.1rem","right":"1.1rem","bottom":"1.1rem","left":"1.1rem"}}},"layout":{"type":"constrained","contentSize":"520px"}} -->
<div class="wp-block-cover is-dark" style="border-radius:14px;padding-top:1.1rem;padding-right:1.1rem;padding-bottom:1.1rem;padding-left:1.1rem;min-height:340px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-45 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:post-terms {"term":"category","fontSize":"small"} /-->

<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"1.7rem","lineHeight":"1.15"}}} /-->

<!-- wp:post-excerpt {"moreText":"Read story","excerptLength":18} /--></div></div>
<!-- /wp:cover -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->