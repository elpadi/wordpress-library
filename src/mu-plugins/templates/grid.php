<section class="grid <?php echo implode(' ', $container_classes); ?>" data-col-count="<?php echo $colcount; ?>">
	<?php foreach ($items as $item): extract($item); ?>
	<article class="grid__item <?php echo implode(' ', $classes); ?>">
		<?php echo $image; ?>
		<a class="foreground uppercase no-underline" href="<?php echo $url; ?>">
			<span class="bg"></span>
			<span class="center-center"><?php echo $title; ?></span>
		</a>
	</article>
	<?php endforeach; ?>
</section>
