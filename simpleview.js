<script language="javascript">

function setSize(senderwindow, newheight) {
	if(newheight<250) {newheight = 250;}
	var iframes = document.getElementById("slideframe");
	var iframeurl, senderurl, iframeid, senderid;
	senderurl = senderwindow.location.href;
	if (iframes != null)
	{
		style.height = newheight + "px";
	}
}
</script>