<section id="<?php echo $html_id; ?>" class="gallery">
	<?php if (!empty($gallery_title)): ?><header><h2><?php echo $gallery_title; ?></h2></header><?php endif; ?>
	<?php foreach ($images as $img): extract($img); ?>
	<article class="gallery__image">
		<img src="<?php echo $src; ?>" alt="" srcset="<?php echo implode(',', $srcs); ?>">
		<div class="gallery__image__info absolute-center">
			<div class="gallery__image__info absolute-center">
				<header class="uppercase tc"><?php echo $title; ?></header>
				<hr>
				<p class="justified"><?php echo $description; ?></p>
			</div>
		</div>
	</article>
	<?php endforeach; ?>
</section>
