<?php
/**
 * Title: Posts Summary List
 * Slug: stagekitwp-theme/posts-summary-list
 * Categories: stagekitwp-theme, query
 * Block Types: core/query
 * Post Types: page, post
 * Description: Clean text-first summary list with metadata and short excerpts.
 */
?>
<!-- wp:query {"queryId":5,"query":{"perPage":8,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"displayLayout":{"type":"list"},"align":"wide"} -->
<div class="wp-block-query alignwide"><!-- wp:post-template -->
<!-- wp:group {"style":{"spacing":{"blockGap":"0.5rem","padding":{"top":"0.5rem","bottom":"0.75rem"},"border":{"bottom":{"color":"var:preset|color|contrast-3","width":"1px"}}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--contrast-3);border-bottom-width:1px;padding-top:0.5rem;padding-bottom:0.75rem"><!-- wp:post-title {"isLink":true,"level":3,"style":{"typography":{"fontSize":"1.35rem","lineHeight":"1.25"}}} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"0.5rem"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:post-date {"fontSize":"small"} /-->

<!-- wp:post-terms {"term":"category","fontSize":"small"} /--></div>
<!-- /wp:group -->

<!-- wp:post-excerpt {"moreText":"Keep reading","excerptLength":22} /--></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->