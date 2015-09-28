<section class="tiles">
	<?php foreach ($tiles as $row): ?>
	<div class="tiles__row">
		<?php foreach ($row as $col): ?>
		<div class="tiles__column" data-width="<?php echo $col->width; ?>" data-height="<?php echo $col->height; ?>">
			<?php foreach ($col as $tile): ?>
			<article class="tile--<?php echo $tile->type; ?> tiles__tile" data-type="<?php echo $tile->type; ?>" data-width="<?php echo $tile->width; ?>" data-height="<?php echo $tile->height; ?>">
				<?php echo $tile->content; ?>
			</article>
			<?php endforeach; ?>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endforeach; ?>
</section>
