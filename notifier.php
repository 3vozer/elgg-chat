<?php
/**
 * Ajax endpoint for chat notifier.
 * 
 * Provides the number of unread messages and a list of
 * latest messages. These are then used to populate the
 * topbar menu item and popup module.
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/engine/start.php');

/**
 * @todo Could we just make a straight query that checks how many
 * "unread_messages" annotations the user has? Is it possible
 * to get also already read messages with the same query?
 */ 

// Do not view edit/delete
elgg_push_context('chat_preview');

$user = elgg_get_logged_in_user_entity();

$chats = elgg_get_entities_from_annotations(array(
	'type' => 'object',
	'subtype' => 'chat',
	'annotation_name' => 'unread_messages',
	'annotation_owner_guids' => $user->getGUID(),
	'limit' => 5,
	/* @todo Ordering doesn't seem to work
	'order_by_annotation' => array(
		'name' => 'unread_messages',
		'direction' => 'desc',
		'as' => 'integer',
	),
	*/
));

$message_count = 0;
$guids = array();
if ($chats) {
	foreach ($chats as $chat) {
		$message_count += $chat->getUnreadMessagesCount();
		$guids[] = $chat->getGUID();
	}
}

// If less than 5 unread chats were found, get other chats
$num_chats = count($guids);
if ($num_chats < 5) {
	$limit = 5 - $num_chats;

	$options = array(
		'type' => 'object',
		'subtype' => 'chat',
		'relationship' => 'member',
		'relationship_guid' => $user->getGUID(),
		'inverse_relationship' => false,
		'limit' => $limit,
	);
	
	// Do not get the chats that were fetched earlier
	if ($num_chats) {
		$guids = implode(',', $guids);
		$options['wheres'] = array("e.guid NOT IN ($guids)");
	}
	
	$more_chats = elgg_get_entities_from_relationship($options);
	
	$chats = array_merge($chats, $more_chats);
}

$preview = elgg_view_entity_list($chats, array('full_view' => false));

if ($preview) {
	// Link to all chats
	$all_chats_link = elgg_view('output/url', array(
		'href' => 'chat/all',
		'text' => elgg_echo('chat:view:all'),
		'class' => '',
	));
	
	$preview .= $all_chats_link;
} else {
	$preview .= elgg_echo('chat:none');
}

$result = new stdClass();
$result->count = $message_count;
$result->preview = $preview;

echo json_encode($result);
