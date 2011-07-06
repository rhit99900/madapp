<?php
$this->load->view('layout/header', array('title'=>'Batch View'));
?>
<link href="<?php echo base_url(); ?>/css/sections/classes/batch_view.css" rel="stylesheet" type="text/css" />

Center: <strong><?php echo $center_name; ?></strong><br />
Batch: <?php echo $batch_name ?><br />
Date: <?php echo $from_date; if($to_date) echo ' to ' . $to_date; ?><br />

<?php
$prev_week = change_week($from_date, -1);
$next_week = change_week($from_date, 1);

if($to_date) {
	$prev_week .= '/'. change_week($to_date, -1);
	$next_week .= '/'. change_week($to_date, 1);
}

?>
<a href="<?php echo site_url('classes/batch_view/'.$batch_id.'/'.$prev_week) ?>">&lt; Previous Week</a>
<a href="<?php echo site_url('classes/batch_view/'.$batch_id.'/'.$next_week) ?>">Next Week &gt;</a>

<form action="<?php echo site_url('classes/batch_view_save'); ?>" method="post">
<table class="data-table info-box-table">
<tr><th>Level</th><th>Feedback</th><th>Students</th><th>Teacher</th><th>Substitute</th><th>Attendence</th><th>Cancelation</th></tr>

<?php
$row_count = 0;
$statuses = array(
			'attended'	=> 'Attended', 
			'absent'	=> 'Absent',
		);
foreach($classes as $class) {
	$teacher_count = count($class['teachers']);
	$rowspan = '';
	if($teacher_count > 1) $rowspan = "rowspan='$teacher_count'";
	
	for($teacher_index=0; $teacher_index < $teacher_count; $teacher_index++) {
		if($teacher_index == 0) {
?>
<tr class="<?php echo ($row_count % 2) ? 'odd' : 'even' ?>">
<td <?php echo $rowspan ?>><a href="<?php echo site_url('classes/edit_class/'.$class['id']) ?>"><?php echo $class['level_name'] ?></a></td>
<td <?php echo $rowspan ?>><?php echo form_dropdown('lesson_id['.$class['id'].']', $all_lessons, $class['lesson_id'], 'style="width:100px;"'); ?></td>
<td <?php echo $rowspan ?>><a href="<?php echo site_url('classes/mark_attendence/'.$class['id']); ?>"><?php echo $class['student_attendence'] ?></a></td>

<?php } ?>
<td><?php echo $class['teachers'][$teacher_index]['name'] ?></td>
<td><?php echo form_dropdown('substitute_id['.$class['id'].']['.$class['teachers'][$teacher_index]['id'].']', $all_user_names, $class['teachers'][$teacher_index]['substitute_id'], 'style="width:100px;"'); ?></td>
<td><?php echo form_dropdown('status['.$class['id'].']['.$class['teachers'][$teacher_index]['id'].']', $statuses, $class['teachers'][$teacher_index]['status'], 'style="width:100px;"'); ?></td>

<?php if($teacher_index == 0) { ?>
<td <?php echo $rowspan ?>>Cancel Class</td>
<?php } ?>
</tr>
<?php
	}
	$row_count++;
} // Level end ?>
</table>

<input type="hidden" name="batch_id" value="<?php echo $batch_id ?>" />
<input type="hidden" name="from_date" value="<?php echo $from_date ?>" />
<input type="hidden" name="to_date" value="<?php echo $to_date ?>" />
<input type="submit" value="Save" name="action" />
</form>

<?php $this->load->view('layout/footer');

// Add or Subtract seven days.
function change_week($date, $add_sub) {
	return date('Y-m-d', strtotime($date) + ($add_sub * (60 * 60 * 24 * 7)));
}