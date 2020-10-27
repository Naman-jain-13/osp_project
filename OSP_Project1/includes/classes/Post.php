<?php
class Post {
	private $user_obj;
	private $con;

	public function __construct($con, $user){
		$this->con = $con;
		$this->user_obj = new User($con, $user);
	}

	public function submitPost($body, $user_to) {

		$body = strip_tags($body); 
		$body = mysqli_real_escape_string($this->con, $body);
		$check_empty = preg_replace('/\s+/', '', $body); 
      
		if($check_empty != "") {

			$date_added = date("Y-m-d H:i:s");

			$added_by = $this->user_obj->getUsername();

			if($user_to == $added_by)
				$user_to = "none";

			$query = mysqli_query($this->con, "INSERT INTO posts VALUES('', '$body', '$added_by', '$user_to', '$date_added', 'no', 'no', '0')");

			$returned_id = mysqli_insert_id($this->con);

			$num_posts = $this->user_obj->getNumPosts();
			$num_posts++;
			$update_query = mysqli_query($this->con, "UPDATE users SET num_posts='$num_posts' WHERE username='$added_by'");
		}
	}

	public function loadPostsFriends() {

		$str = "";
		$data_query = mysqli_query($this->con, "SELECT * FROM posts WHERE deleted='no' ORDER BY id DESC");
		$time_message = "";
		$userLoggedIn = $this->user_obj->getUsername();

		while($row = mysqli_fetch_array($data_query))
		{

			$id = $row['id'];
			$body = $row['body'];
			$added_by = $row['added_by'];
			$date_time = $row['date_added'];
		
			if($row['user_to']== "none")
			{
				$user_to="";
			}
			else
			{
				$user_to_obj = new User($this->con, $row['user_to']);
				$user_to_name = $user_to_obj->getFirstandLastName();
				$user_to = "to <a href='" . $row['user_to'] ."'>" . $user_to_name . "</a>";	
			}

			$added_by_obj = new User($this->con, $added_by);
			if($added_by_obj->isClosed()) {
				continue;}

				
			$user_logged_obj = new User($this->con, $userLoggedIn);
			if($user_logged_obj->isFriend($added_by))

			{

					if($userLoggedIn == $added_by)
						$delete_button = "<button class='delete_button btn-danger' 
					style='font-weight:bold; 
					padding: 10px 10px 25px 10px;' id='post$id'> X";
					else
						$delete_button = "";

					$user_details_query = mysqli_query($this->con, "SELECT first_name,last_name,profile_pic FROM users WHERE username='$added_by'");
					$user_row = mysqli_fetch_array($user_details_query);
					$first_name = $user_row['first_name'];
					$last_name = $user_row['last_name'];
					$pico = $user_row['profile_pic'];
					?>
					
				<script >toggle2
					
					function toggle<?php echo $id; ?>() {
					var target  = $(event.target);
					if(!target.is("a"))
					{
					var element = document.getElementById("toggleComment<?php echo $id; ?>");				 
					if(element.style.display == "block") 
						element.style.display = "none";
					else 
						element.style.display = "block";
					}
						}
				</script>

				<?php

					$comments_check = mysqli_query($this->con, "SELECT * FROM comments WHERE post_id='$id'");
					$comments_check_num = mysqli_num_rows($comments_check);

					$date_time_now = date("Y-m-d H:i:s");
					$start_date = new DateTime($date_time);
					$end_date = new DateTime($date_time_now);

					$interval = $start_date->diff($end_date);

					if($interval->y >=1)
					{
						if($interval->y == 1)
							$time_message = $interval->y. " year ago";
						else
							$time_message = $interval->y. " years ago";
					}
					else if($interval->m >=1)
					{
						if($interval->d == 0)
							$days = " ago";
						else if($interval->d == 1)
							$days = $interval->d . " day ago";
						else
							$days = $interval->d . " days ago";

						if($interval->m == 1)
							$time_message = $interval->m . " month" . $days;
						else
							$time_message = $interval->m . " month" . $days;

					}
					else if ($interval->d >= 1) {
						if($interval->d == 1)
							$time_message = " Yesterday";
						else
							$time_message = $interval->d . " days ago";
					}
					else if ($interval->h >= 1) 
					{
						if($interval->h == 1)
							$time_message = $interval->h . " hour ago";
						else
							$time_message = $interval->h . " hours ago";
					}
					else if ($interval->i >= 1) 
					{
						if($interval->i == 1)
							$time_message = $interval->i . " minute ago";
						else
							$time_message = $interval->i . " minutes ago";
					}
					else if ($interval->s >= 1) 
					{
						if($interval->s == 1)
							$time_message = $interval->s . " second ago";
						else
							$time_message = $interval->s . " seconds ago";
					}
					else{
						$time_message = "Just Now";
					}
				
					
					$str .= "<div class='status_post' onClick='javascript:toggle$id()'>
										
										<div class='post_profile_pic'>
											<img src='$pico' width='50'>
										</div>

										<div class='posted_by' style='color:#ACACAC;'>
											<a href='$added_by'> $first_name $last_name </a> <?php echo $user_to; ?> &nbsp;&nbsp;&nbsp;&nbsp;$time_message
											$delete_button
										</div>
										<br>
										<div id='post_body'>
											$body
											<br>
											<br>
										</div>

							</div>

							<div class='newsfeedPostOptions'>
								Comments($comments_check_num) &nbsp;&nbsp;&nbsp;&nbsp;
								<iframe src='like.php?post_id=$id' id='like_iframe' scrolling='no'></iframe>
							</div>

							<div class='post_comment' id='toggleComment$id' style='display:none;'> 
								<iframe src='comment.php?post_id=$id' id='comment_iframe'> </iframe>
							</div>
							<hr>";
						
					}

					?>
					<script >
						
						$(document).ready(function(){

							$('#post<?php echo $id; ?>').on('click', function(){
								bootbox.confirm("Are you sure you want to delete this post?", function(result){
									$.post('delete_post.php?post_id=<?php echo $id; ?>', {result:result});
									if(result)
										location.reload()
								});
							});
						});

					</script>
					<?php
				}
				echo $str;
	}


	public function loadProfilePosts($data) {

		$profileusername = $data['profileUsername']; 
		$str = "";
		$time_message = "";
		$userLoggedIn = $this->user_obj->getUsername();
		$data_query = mysqli_query($this->con, "SELECT * FROM posts WHERE deleted='no' and added_by='$profileusername' ORDER BY id DESC");

		while($row = mysqli_fetch_array($data_query))
		{

			$id = $row['id'];
			$body = $row['body'];
			$added_by = $row['added_by'];
			$date_time = $row['date_added'];
		
			if($row['user_to']== "none")
			{
				$user_to="";
			}
			else
			{
				$user_to_obj = new User($this->con, $row['user_to']);
				$user_to_name = $user_to_obj->getFirstandLastName();
				$user_to = "to <a href='" . $row['user_to'] ."'>" . $user_to_name . "</a>";	
			}

			$added_by_obj = new User($this->con, $added_by);
			if($added_by_obj->isClosed()) {
				continue;}

				
			$user_logged_obj = new User($this->con, $userLoggedIn);
			if($user_logged_obj->isFriend($added_by))

			{

					if($userLoggedIn == $added_by)
						$delete_button = "<button class='delete_button btn-danger' 
							style='
							font-weight:bold; 
							padding: 10px 10px 25px 10px;' 
							id='post$id'> X";
					else
						$delete_button = "";

					$user_details_query = mysqli_query($this->con, "SELECT first_name,last_name,profile_pic FROM users WHERE username='$added_by'");
					$user_row = mysqli_fetch_array($user_details_query);
					$first_name = $user_row['first_name'];
					$last_name = $user_row['last_name'];
					$pico = $user_row['profile_pic'];
					?>
					
				<script >toggle2
					
					function toggle<?php echo $id; ?>() {
					var target  = $(event.target);
					if(!target.is("a"))
					{
					var element = document.getElementById("toggleComment<?php echo $id; ?>");				 
					if(element.style.display == "block") 
						element.style.display = "none";
					else 
						element.style.display = "block";
					}
						}
				</script>

				<?php

					$comments_check = mysqli_query($this->con, "SELECT * FROM comments WHERE post_id='$id'");
					$comments_check_num = mysqli_num_rows($comments_check);

					$date_time_now = date("Y-m-d H:i:s");
					$start_date = new DateTime($date_time);
					$end_date = new DateTime($date_time_now);

					$interval = $start_date->diff($end_date);

					if($interval->y >=1)
					{
						if($interval->y == 1)
							$time_message = $interval->y. " year ago";
						else
							$time_message = $interval->y. " years ago";
					}
					else if($interval->m >=1)
					{
						if($interval->d == 0)
							$days = " ago";
						else if($interval->d == 1)
							$days = $interval->d . " day ago";
						else
							$days = $interval->d . " days ago";

						if($interval->m == 1)
							$time_message = $interval->m . " month" . $days;
						else
							$time_message = $interval->m . " month" . $days;

					}
					else if ($interval->d >= 1) {
						if($interval->d == 1)
							$time_message = " Yesterday";
						else
							$time_message = $interval->d . " days ago";
					}
					else if ($interval->h >= 1) 
					{
						if($interval->h == 1)
							$time_message = $interval->h . " hour ago";
						else
							$time_message = $interval->h . " hours ago";
					}
					else if ($interval->i >= 1) 
					{
						if($interval->i == 1)
							$time_message = $interval->i . " minute ago";
						else
							$time_message = $interval->i . " minutes ago";
					}
					else if ($interval->s >= 1) 
					{
						if($interval->s == 1)
							$time_message = $interval->s . " second ago";
						else
							$time_message = $interval->s . " seconds ago";
					}
					else{
						$time_message = "Just Now";
					}
				
					$str .= "<div class='status_post' onClick='javascript:toggle$id()'>
										
										<div class='post_profile_pic'>
											<img src='$pico' width='50'>
										</div>

										<div class='posted_by' style='color:#ACACAC;'>
											<a href='$added_by'> $first_name $last_name </a> <?php echo $user_to; ?> &nbsp;&nbsp;&nbsp;&nbsp;$time_message
											$delete_button
										</div>
										<br>
										<div id='post_body'>
											$body
											<br>
											<br>
										</div>

							</div>

							<div class='newsfeedPostOptions'>
								Comments($comments_check_num) &nbsp;&nbsp;&nbsp;&nbsp;
								<iframe src='like.php?post_id=$id' id='like_iframe' scrolling='no'></iframe>
							</div>

							<div class='post_comment' id='toggleComment$id' style='display:none;'> 
								<iframe src='comment.php?post_id=$id' id='comment_iframe'> </iframe>
							</div>
							<hr>";
					}

					?>
					<script >
						
						$(document).ready(function(){

							$('#post<?php echo $id; ?>').on('click', function(){
								bootbox.confirm("Are you sure you want to delete this post?", function(result){
									$.post("delete_post.php?post_id=<?php echo $id; ?>", {result:result});
									if(result)
										location.reload()
								});
							});
						});

					</script>
					<?php
				}
				echo $str;			
	}
}
?>