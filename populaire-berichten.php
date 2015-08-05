<?php
/*
Plugin Name:	Populaire berichten
Plugin URI:		http://www.ritchiejacobs.be
Description:	Een widget om de meest bekeken berichten weer te geven.
Version:		1.0
Author:			Ritchie Jacobs
Author URI:		http://www.ritchiejacobs.be
*/

add_action('widgets_init', function(){ register_widget( 'Populaire_Berichten' ); });
add_action("wp_head", "update_post_views_counter");

class Populaire_Berichten extends WP_Widget
{
    public function __construct()
    {
        parent::__construct("Populaire_Berichten", "Populaire berichten", array("description" => __("Deze plugin toont de meest bekeken berichten.")));
    }

    function widget($args, $instance)
	{
	    extract($args);

	    $title = $instance['title'];
	    $stats = $instance['stats'];

	    echo $before_widget;

	    if($title)
	    {
	        echo $before_title . $title . $after_title ;
	    }

		if($stats == "week")
		{
			$posts_array = json_decode(get_option("top_views_week_" . date('W_Y')), true);
		}
		else if($stats == "month")
		{
			$posts_array = json_decode(get_option("top_views_month_" . date('M_Y')), true);
		}
		else
		{
			$posts_array = json_decode(get_option("top_views", array()), true);
		}

		if(!empty($posts_array))
		{
			?>
			<ol>
			<?php
			foreach($posts_array as $key => $value)
			{
				$title = get_the_title($key);
				$image = get_the_post_thumbnail($key, 'thumbnail');

				?>
				<li class="post-views">
					<a href="<?php echo get_permalink($key); ?>">
						<div><?php echo $image ?></div>
						<h3><?php echo $title ?></h3>
					</a>
				</li>
				<?php
			}
			?>
			</ol>
			<?php
		}

	    echo $after_widget;
	}

    function form($instance)
	{
	    if($instance)
	    {
	        $title = esc_attr($instance['title']);
	        $stats = esc_attr($instance['stats']);
	    }
	    else
	    {
	        $title = '';
	        $stats = '';
	    }

	    ?>
	    <p>
	        <label for="<?php echo $this->get_field_id('title'); ?>"><?php echo "Titel"; ?></label>
	        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
	    </p>
	    <p>
	        <label for="<?php echo $this->get_field_id('stats'); ?>"><?php echo "Type"; ?></label><br>
	        <input type="radio" name="<?php echo $this->get_field_name('stats'); ?>" value="week" <?php if($stats == "week") { echo "checked"; } ?>>Wekelijks<br>
	        <input type="radio" name="<?php echo $this->get_field_name('stats'); ?>" value="month" <?php if($stats == "month") { echo "checked"; } ?>>Maandelijks<br>
	        <input type="radio" name="<?php echo $this->get_field_name('stats'); ?>" value="year" <?php if($stats == "year") { echo "checked"; } ?>>Sinds begin
	    </p>
	    <?php
	}

    function update($new_instance, $old_instance)
	{
	    $instance = $old_instance;
	    $instance['title'] = strip_tags($new_instance['title']);
	    $instance['stats'] = strip_tags($new_instance['stats']);
	    return $instance;
	}
}

function update_post_views_counter()
{
	if(is_single() && get_post_type() == "post")
	{
		/* Top posts */
		$id = get_the_ID();
		$views = get_post_meta($id, "total_views", true);

		if($views == "")
		{
			$views = 1;
			add_post_meta($id, "total_views", $views);
		}
		else
		{
			$views++;
			update_post_meta($id, "total_views", $views);
		}

		maintain_top_posts($id, $views);


		$views_week = get_post_meta($id, "total_views_week_" . date('W_Y'), true);

		if($views_week == "")
		{
		  $views_week = 1;
		  add_post_meta($id, "total_views_week_" . date('W_Y'), $views_week);
		}
		else
		{
		  $views_week++;
		  update_post_meta($id, "total_views_week_" . date('W_Y'), $views_week);
		}

		maintain_top_posts_weekly($id, $views_week);

		/* Top posts - monthly */
		$views_month = get_post_meta($id, "total_views_month_" . date('M_Y'), true);

		if($views_month == "")
		{
		  $views_month = 1;
		  add_post_meta($id, "total_views_month_" . date('M_Y'), $views_month);
		}
		else
		{
		  $views_month++;
		  update_post_meta($id, "total_views_month_" . date('M_Y'), $views_month);
		}

		maintain_top_posts_monthly($id, $views_month);
	}
}

function maintain_top_posts($id, $views)
{
	$number_of_top_posts = 10;
	$top_views_array = get_option("top_views", "");

	if(empty($top_views_array)) {
		$top_views_array = array();
		$top_views_array[$id] = $views;
	}
	else
	{
		$top_views_array = json_decode($top_views_array, true);
		$size  = sizeof($top_views_array);

		if($size < $number_of_top_posts) {
			$top_views_array[$id] = $views;
			arsort($top_views_array);
			$top_views_array = array_slice($top_views_array, 0, $number_of_top_posts, true);
		}
		else if($size >= $number_of_top_posts)
		{
			$top_views_array[$id] = $views;
			arsort($top_views_array);
			$top_views_array = array_slice($top_views_array, 0, $number_of_top_posts, true);
		}
	}

	update_option("top_views", json_encode($top_views_array));
}

function maintain_top_posts_weekly($id, $views)
{
  $number_of_top_posts = 10;

  $top_views_array = get_option("top_views_week_" . date('W_Y'), "");

  if(empty($top_views_array))
  {
    $top_views_array = array();
    $top_views_array[$id] = $views;
  }
  else
  {
    $top_views_array = json_decode($top_views_array, true);
    $size  = sizeof($top_views_array);

    if($size < $number_of_top_posts)
    {
      $top_views_array[$id] = $views;
      arsort($top_views_array);

      $top_views_array = array_slice($top_views_array, 0, $number_of_top_posts, true);

    }
    else if($size >= $number_of_top_posts)
    {
      $top_views_array[$id] = $views;
      arsort($top_views_array);
      $top_views_array = array_slice($top_views_array, 0, $number_of_top_posts, true);
    }
  }

  update_option("top_views_week_" . date('W_Y'), json_encode($top_views_array));
}

function maintain_top_posts_monthly($id, $views)
{
  $number_of_top_posts = 10;

  $top_views_array = get_option("top_views_month_" . date('M_Y'), "");

  if(empty($top_views_array))
  {
    $top_views_array = array();
    $top_views_array[$id] = $views;
  }
  else
  {
    $top_views_array = json_decode($top_views_array, true);
    $size  = sizeof($top_views_array);

    if($size < $number_of_top_posts)
    {
      $top_views_array[$id] = $views;
      arsort($top_views_array);

      $top_views_array = array_slice($top_views_array, 0, $number_of_top_posts, true);

    }
    else if($size >= $number_of_top_posts)
    {
      $top_views_array[$id] = $views;
      arsort($top_views_array);
      $top_views_array = array_slice($top_views_array, 0, $number_of_top_posts, true);
    }
  }

  update_option("top_views_month_" . date('M_Y'), json_encode($top_views_array));
}
