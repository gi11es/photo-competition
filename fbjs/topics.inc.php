<script><!--

function deleteTopic(topic_id) {
	document.getElementById('deleteTopicId').setValue(topic_id);
	document.getElementById('deleteTopicForm').submit();
}

function voteTopic(topic_id, value) {
	document.getElementById('voteTopicId').setValue(topic_id);
	document.getElementById('voteTopicValue').setValue(value);
	document.getElementById('voteTopicForm').submit();
}
//--></script>