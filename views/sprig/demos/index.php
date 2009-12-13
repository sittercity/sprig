<?php foreach ($students as $student): ?>

<h1><?php echo HTML::anchor('sprig_demos/student/'.$student->id, $student->name) ?></h1>

<h2>Classes</h2>
<ul>
<?php foreach ($student->classes as $class): ?>
	<li><?php echo $class->name ?> (<?php echo $class->level ?>)</li>
<?php endforeach ?>
</ul>

<h2>Memberships</h2>
<ul>
<?php foreach ($student->memberships as $membership): ?>
	<li>Joined "<?php echo $membership->club->name ?>" on <?php echo $membership->verbose('joined_on') ?></li>
<?php endforeach ?>
</ul>

<?php endforeach ?>
