<?php if ($is_fullscreen): ?>
<noscript><div class="slideshow"></noscript>
<script>document.write('<div id="<?php echo $html_id; ?>" class="<?php echo implode(' ', $classes); ?>" style="height:' + document.documentElement.clientHeight + 'px;">');</script>
<?php else: ?>
<div id="<?php echo $html_id; ?>" class="<?php echo implode(' ', $classes); ?>">
<?php endif; ?>
	<section class="slideshow__images">
		<?php foreach ($images as $img): extract($img); ?>
		<img class="slideshow__image" src="<?php echo $src; ?>" alt="" srcset="<?php echo implode(',', $srcs); ?>" data-title="<?php echo $title; ?>">
		<?php endforeach; ?>
	</section>
	<p class="slideshow__image-info">
		<?php if ($titles_are_links): ?>
		<a class="info__title" rel="nofollow" target="_blank" href="<?php echo $first['url']; ?>"><?php echo $first['title']; ?></a>
		<?php else: ?>
		<span class="info__title"><?php echo $first['title']; ?></span>
		<?php endif; ?>
	</p>
	<p class="slideshow__dots">
		<button class="slideshow__dot selected" data-index="0"><object type="image/svg+xml" data="<?php echo get_template_directory_uri(); ?>/img/icons/slideshow-dot.svg"><span class="svg-fallback"><?php echo $first['title']; ?></span></object></button>
		<?php for ($i = 1; $i < count($images); $i++): ?>
		<button class="slideshow__dot" data-index="<?php echo $i; ?>"><object type="image/svg+xml" data="<?php echo get_template_directory_uri(); ?>/img/icons/slideshow-dot.svg"><span class="svg-fallback"><?php echo $images[$i]['title']; ?></span></object></button>
		<?php endfor; ?>
	</p>
</div>

