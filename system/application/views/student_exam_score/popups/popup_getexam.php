<?php $this->load->view('layout/thickbox_header'); ?>
<script type="text/javascript" src="<?php echo base_url()?>js/jquery.min.js"></script>
<script>
function mark_view_divs(exam_id)
{
	if(exam_id != '-1') {
			$('#loading').show();
			$('#score_div').show();
            $.ajax({
            type: "POST",
            url: "<?php echo site_url('exam/getexam_subjects_name')?>",
            data: "exam_id="+exam_id,
            success: function(msg){
           		$('#loading').hide();
            	$('#score_divs').html(msg);
            }
            });
	}
	else
	{
	
	$('#score_div').hide();
	}
}
</script> 
<div id="content" class="clear">
<h2>Add Exam Mark</h2>
<?php echo $this->session->flashdata('success'); ?>
<div id="sub-chapter-header">
<ul class="form city-form">
<li>

      <?php $exam_details = $exam_details->result_array(); ?>
      <label>Select Exam : </label>
      <select name="select_exam" id="select_exam" class="medium" onchange="javascript:mark_view_divs(this.value);">
      <option value="-1" selected="selected">-- select exam --</option>
      <?php foreach($exam_details as $row): ?>
      	<option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
      <?php endforeach; ?>	
      </select>
</li>
</ul>
  <div id="loading" name="loading" style="display:none;" align="center">
    <img src="<?php echo base_url()?>images/ico/loading_1.gif" height="18" width="18" style="border: none;margin-left: ;" /> 
    <span style="color:#000;font-weight:bold;">loading...</span>
  </div><div id="score_divs"> </div>
  </div>
  </div>
  