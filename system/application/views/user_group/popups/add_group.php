<link rel="stylesheet" type="text/css" href="<?php echo base_url()?>css/g.css" />
<link rel="stylesheet" type="text/css" href="<?php echo base_url()?>css/l.css" />
<link rel="stylesheet" type="text/css" href="<?php echo base_url()?>css/bk.css" />
<link rel="stylesheet" type="text/css" href="<?php echo base_url()?>css/r.css" />
<link rel="stylesheet" type="text/css" href="<?php echo base_url()?>css/validation.css" />
<script type="text/javascript" src="<?php echo base_url()?>js/jquery.min.js"></script>
	<form id="formEditor" class="mainForm clear"action="<?=site_url('user_group/addgroup_name')?>" method="post" style="width:500px;">
	<fieldset class="clear" style="margin-top:50px;width:500px;margin-left:-30px;">
    
	<div class="field clear" style="width:600px;"> 
           <label for="txtName">Group Name : </label>
           <input id="groupname" name="groupname"  type="text" /> 
    </div>
    <div class="field clear" style="width:550px;"> 
     	   <input style="margin-left:250px;" id="btnSubmit" class="button primary" type="submit" value="Submit" />
    </div>
    </fieldset>
    </form>