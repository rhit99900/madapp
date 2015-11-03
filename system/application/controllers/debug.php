<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package         MadApp
 * @author          Rabeesh
 * @copyright       Copyright (c) 2008 - 2010, OrisysIndia, LLP.
 * @link            http://orisysindia.com
 * @since           Version 1.0
 * @filesource
 */
class Debug extends Controller {
   	function Debug() {
        parent::Controller();
		$this->load->library('session');
		$this->load->library('navigation');
        $this->load->library('user_auth');
		$this->load->library('validation');
		$this->load->helper('url');
        $this->load->helper('form');
		$this->load->model('center_model');
		$this->load->model('project_model');
		$this->load->model('users_model');
		$this->load->model('city_model');
		$this->load->model('batch_model');
		$this->load->model('level_model');
		$this->load->model('class_model');
	}


	/// An easy way to delete a lot of students in the given centers. USE WITH CARE!
	function delete_students_in_center() {
		$centers = array(); // Put the center IDs here - all the kids in thes will be deleted.

		if(!$centers) return;
		
		$student_ids = $this->users_model->db->query("SELECT id FROM `Student` WHERE center_id IN(".implode(',',$centers).")")->result();
		print "Total Students: " . count($student_ids) . '<br />';
		//return;
		
		foreach($student_ids as $row) {
			$id = $row->id;
			print "$id,";
			$this->db->query("DELETE FROM Student WHERE id=$id");
			$this->db->query("DELETE FROM StudentLevel WHERE student_id=$id");
			$this->db->query("DELETE FROM StudentClass WHERE student_id=$id");
		}
	}
	
	function delete_batch_and_level_of_deativated_centers() {
		$deactivated_centers = array_keys(idNameFormat($this->db->query("SELECT id,name FROM Center WHERE status='0'")->result()));
		
		$affected_rows = 0;
		$this->db->query("UPDATE Batch SET status='0' WHERE center_id IN (".implode(',', $deactivated_centers).")");
		$affected_rows += $this->db->affected_rows();
		$this->db->query("UPDATE Level SET status='0' WHERE center_id IN (".implode(',', $deactivated_centers).")");
		$affected_rows += $this->db->affected_rows();
		
		print "Affected: $affected_rows";
	}
	
	function delete_classes_of_deactivated_centers() {
		$deactivated_batches = colFormat($this->db->query("SELECT id FROM Batch WHERE status='0'")->result());
		
		$delete_class = colFormat($this->db->query("SELECT id FROM Class WHERE batch_id IN (".implode(',', $deactivated_batches).")")->result());
		foreach($delete_class as $class_id) {
			print $class_id . "<br />";
			$this->db->query("DELETE FROM Class WHERE class_on > NOW()");
			if($this->db->affected_rows()) {
				$this->db->query("DELETE FROM UserClass WHERE class_id='$class_id'");
			}
		}
		
	}
	
	function delete_exam_marks_of_city($city_id) {
		$exam_events_in_city = colFormat($this->db->query("SELECT id FROM Exam_Event WHERE city_id='$city_id'")->result());
		
		$count = 0;
		foreach($exam_events_in_city as $exam_event_id) {
			$count++;
			$this->db->query("DELETE FROM Exam_Mark WHERE exam_event_id=$exam_event_id");
		}
		$this->db->query("DELETE FROM Exam_Event WHERE city_id=$city_id");
		
		print "Deleted $count exams.";
	}
	 
	/**
	 * Use this to clear off entire regions of the MADSheet - use this with care. Deletes data without any hope of retrival.
	 */
	function delete_all_class_of_batch($batch_id) {
		echo "Deleting all Class in Batch <strong>$batch_id</strong>: ";
		$classes_in_batch = colFormat($this->db->query("SELECT id FROM Class WHERE batch_id='$batch_id'")->result());
		$this->db->query("DELETE FROM Class WHERE batch_id='$batch_id'");
		
		foreach($classes_in_batch as $class_id) {
			$this->db->query("DELETE FROM UserClass WHERE class_id='$class_id'");
			$this->db->query("DELETE FROM StudentClass WHERE class_id='$class_id'");
			echo "$class_id, ";
		}
		echo " - Done.<br />";
	}
	
	function delete_all_class_in_center($center_id) {
		echo "Deleting all class in center <strong>$center_id</strong>...<br />";
		$year = $this->session->userdata('year');
		
		$batches_in_center = colFormat($this->db->query("SELECT id FROM Batch WHERE center_id='$center_id' AND year='$year'")->result());
		
		foreach($batches_in_center as $batch_id) {
			$this->delete_all_class_of_batch($batch_id);
		}
		echo "All is done.";
	}
	
	/// Bulk add classes - add class to the given batch from the said date - this will add classes until we encounter a day which has classes already - or the current date is hit.
	// Use this to add Back classes.
	// Code taken from classes::add_manually_save
	function add_class_to_batch($batch_id, $from_date) {
		$batch = $this->batch_model->get_batch($batch_id);
		$dt_from_date = new DateTime($from_date);
		
		if($batch->day != $dt_from_date->format('w')) {
			print "Day Miss-match. Batch $batch_id's day is {$batch->day} while $from_date is ".$dt_from_date->format('w');
			return;
		}
		
		$day_count = 0;
		echo "Making classes...<br />";
		while(date_diff($dt_from_date, new DateTime())->format("%r%a") > 0) { // Make sure we are in the past.
		
			$class_date = $dt_from_date->format("Y-m-d") . ' ' . $batch->class_time;
			$user_class_id = array(); 
			$teachers = $this->batch_model->get_batch_teachers($batch->id);
			foreach($teachers as $teacher) {
				// Make sure its not already inserted.
				if(!$this->class_model->get_by_teacher_date($teacher->id, $class_date, $batch->id, $teacher->level_id)) {
					$user_class_id[] = $this->class_model->save_class(array(
						'batch_id'	=> $batch->id,
						'level_id'	=> $teacher->level_id,
						'teacher_id'=> $teacher->id,
						'substitute_id'=>0,
						'class_on'	=> $class_date,
						'status'	=> 'projected'
					));
					echo "Classes added for $class_date : " . implode(",", $user_class_id) . "<br />";
				} else {
					return; // Exit if we encounter a class thats made in that time period.
				}
			}
			$dt_from_date->add(new DateInterval('P7D')); // Jump to next week
			$day_count++;
			if($day_count > 50) return; // Too long an interval. Don't bother creating classes.
		}
	}

	// Add Back classes for the entire city.
	function add_back_classes($city_id=0, $center_id = 0) {
		if(!$center_id)
			$all_centers = $this->center_model->get_all($city_id);
		else
			$all_centers = $this->center_model->get_info($center_id);

		foreach($all_centers as $center) {
			$batches = $this->batch_model->get_batches_in_center($center->id);
			$center_class_starts_on = $center->class_starts_on;
			if($center_class_starts_on == '0000-00-00') {
				print "Invalid Start date for {$center->name}({$center->id}). Not generating class.<br />";
				continue;
			}
			$start_on_day = date('w', strtotime($center_class_starts_on));
			print "Generating back classes for " . $center->name . "<br />";

			foreach ($batches as $batch) {
				if($batch->day == $start_on_day) {
					$class_starts_on = date('Y-m-d', strtotime($center_class_starts_on));
					$this->add_class_to_batch($batch->id, $class_starts_on);
					echo "= $batch->id : $class_starts_on<br />";

				} else if($batch->day < $start_on_day) {
					$difference = ($batch->day + 7) - $start_on_day;
					$class_starts_on = date('Y-m-d', strtotime($center_class_starts_on) + ($difference * 24 * 60 * 60));
					$this->add_class_to_batch($batch->id, $class_starts_on);
					echo "< $batch->id : $class_starts_on<br />";

				} else if($batch->day > $start_on_day) {
					$difference = $batch->day + $start_on_day;
					$class_starts_on = date('Y-m-d', strtotime($center_class_starts_on) + ($difference * 24 * 60 * 60));
					$this->add_class_to_batch($batch->id, $class_starts_on);
					echo "> $batch->id : $class_starts_on<br />";
				}
			}
			print "<br />";
		}

	}


	// For some reason I havent figured out yet, there are duplicate users in madsheet. This deletes them. Have to figured out why later.
	function delete_duplicate_class_entries() {
		// Find users with multiple levels in this year.
		$teachers = $this->users_model->db->query("SELECT U.id FROM User U INNER JOIN UserGroup UG ON U.id=UG.user_id WHERE UG.group_id=9 AND UG.year='2015'")->result();

		foreach ($teachers as $t) {
			// Find the actual levels
			$offical_batch_level = $this->users_model->db->query("SELECT batch_id,level_id FROM UserBatch UB INNER JOIN Batch B ON B.id=UB.batch_id WHERE B.year='2015' AND UB.user_id={$t->id}")->result();
			if(!$offical_batch_level) continue;

			// Find which batch/level combo is in UserBatch
			$other_classes_they_are_in = $this->users_model->db->query("SELECT C.* FROM Class C 
						INNER JOIN UserClass UC ON UC.class_id=C.id 
						WHERE UC.user_id={$t->id} AND DATE_FORMAT(C.class_on, '%Y')='2015'")->result();

			foreach($other_classes_they_are_in as $cl) {
				if(($cl->batch_id != $offical_batch_level[0]->batch_id) or ($cl->level_id != $offical_batch_level[0]->level_id)) {
					// Delete other instances from Class Table
					$this->users_model->db->query("DELETE FROM Class WHERE id={$cl->id}");
					$this->users_model->db->query("DELETE FROM UserClass WHERE class_id={$cl->id}");
					print "Deleting duplicate entries for {$t->id}<br />";
				}
			}
		}
	}


	function delete_usergroup_after_moving_members_to_other_group($usergroup_id_to_delete, $other_usergroup_id) {
		$this->users_model->db->query("DELETE FROM `Group` WHERE id=$usergroup_id_to_delete");
		$this->users_model->db->query("UPDATE `UserGroup` SET group_id=$other_usergroup_id WHERE group_id=$usergroup_id_to_delete");
	}

	function move_classes_from_one_level_to_another($old_batch, $old_level, $new_batch) {
		$this->batch_model->db->query("UPDATE Class SET batch_id=$new_batch WHERE batch_id=$old_batch AND level_id=$old_level");
		print "Updated " . $this->batch_model->db->affected_rows() . " class.";
	}
	
	/// This will delete the duplacated classes in the given batch. Usually this happens because the time of the class has been changed.
	function delete_duplicate_classes($batch_id) {
		$all_classes = $this->batch_model->db->query("SELECT * FROM Class WHERE batch_id=$batch_id")->result();

		$existing_level_dates = array();
		foreach ($all_classes as $cl) {
			$key = $cl->level_id . ':' . date('Y-m-d', strtotime($cl->class_on)); // Create a key with the level id and the date of the class. If the keys of multiple classes match, that means its duplate class.

			// Its a match! Duplate class.
			if(isset($existing_level_dates[$key])) {
				print "Duplicated at " . $cl->id . "<br />\n";

				// First, check to make sure this one has no data
				// $student_attendance_data = $this->class_model->get_attendence($cl->id); // Don't check for student attendance. No chance people are going to mark teacher attendance for one and student attendance for other.
				$vol_attendance_data = $this->class_model->get_class($cl->id);

				if(		!($vol_attendance_data['teachers']) 
					or 	($cl->status == 'projected') 
					or 	($vol_attendance_data['teachers'][0]['status'] == 'projected')) {
						// No volunteer attendance data - delete with impunity.
						$this->class_model->delete($cl->id); // Delete class
						print "Deleting: $cl->id<br \>";
				
				} else { // If we have data in the current class, just delete the other class. Blindly.
					$this->class_model->delete($existing_level_dates[$key]);
					print "Deleted: $cl->id<br \>";
				}

			// Class is unique(so far). So save it to the hash table
			} else {
				$existing_level_dates[$key] = $cl->id;
			}
		}

		dump($existing_level_dates);
	}


	function delete_classes_of_user_in_given_batch_and_level($user_id, $batch_id, $level_id=0, $from_date='', $to_date='') {
		$level_check = '';
		$date_check = '';
		if($level_id) $level_check = "AND C.level_id=$level_id";
		if($from_date) $date_check .= "AND C.class_on >= '$from_date'";
		if($to_date) $date_check .= "AND C.class_on <= '$to_date'";

		$classes = $this->batch_model->db->query("SELECT C.id AS class_id,UC.id AS user_class_id 
			FROM Class C 
			INNER JOIN UserClass UC ON C.id=UC.class_id 
			WHERE UC.user_id=$user_id AND C.batch_id=$batch_id $level_check $date_check")->result();

		foreach ($classes as $c) {
			$this->batch_model->db->query("DELETE FROM UserClass WHERE id=" . $c->user_class_id);

			$other_user_for_class = $this->batch_model->db->query("SELECT id FROM UserClass WHERE class_id={$c->class_id}")->result();
			if(count($other_user_for_class) == 0) { // If there are no other teachers, delete the class too.
				$this->batch_model->db->query("DELETE FROM Class WHERE id={$c->class_id}");

				// If class is deleted, delete student attandance too.
				$this->batch_model->db->query("DELETE FROM StudentClass WHERE class_id={$c->class_id}");
			}
		}
		print "Deleted " . count($classes) . " classes.";

	}
}

