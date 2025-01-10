<?php

namespace Twitch;

class UserProfile extends \stdClass
{
	public int $id;
	public string $login;
	public string $display_name;
	public string $type;
	public string $broadcaster_type;
	public string $description;
	public string $profile_image_url;
	public string $offline_image_url;
	public int $view_count;
	public string $created_at;

	public function __construct(\stdClass $object)
	{
		foreach ($object as $property => $value) {
			$this->$property = $value;
		}
	}
}
