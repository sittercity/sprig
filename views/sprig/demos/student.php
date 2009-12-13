<?php echo Form::open() ?>

<?php if ( ! empty($errors)): ?>
<ul>
<?php foreach ($errors as $error): ?>
	<li><?php echo $error ?></li>
<?php endforeach ?>
</ul>
<?php endif ?>

<dl>
<?php foreach ($student->inputs() as $label => $input): ?>
	<dt><?php echo $label ?></dt>
	<dd><?php echo $input ?></dd>

<?php endforeach ?>
</dl>

<?php echo Form::submit(NULL, 'Save') ?>

<?php echo Form::close() ?>