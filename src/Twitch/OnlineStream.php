<?php

namespace Twitch;

class OnlineStream extends \stdClass
{
	public int $id;
	public int $user_id;
	public string $user_name;
	public int $game_id;
	public string $game_name;
	public string $type;
	public string $title;
	public int $viewer_count;
	public string $started_at;
	public string $language;
	public string $thumbnail_url;
	public array $tag_ids;

	public function __construct(\stdClass $object)
	{
		foreach ($object as $property => $value) {
			$this->$property = $value;
		}
	}
}
