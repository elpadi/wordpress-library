<select id="<?php echo $name; ?>" name="<?php echo $name; ?>">
	<?php foreach ($options as $key => $title): ?>
	<option value="<?php echo $key; ?>" <?php if ($key === $value) echo 'selected'; ?>><?php echo $title; ?></option>
	<?php endforeach; ?>
</select>
