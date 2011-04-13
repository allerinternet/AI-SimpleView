/*function setSize(senderwindow, newheight) {
	if(newheight < 250) {
		newheight = 250;
	}
	var iframes = document.getElementById("slideframe");
	var iframeurl, senderurl, iframeid, senderid;
	senderurl = senderwindow.location.href;
	if (iframes != null)
	{
		iframes.style.height = newheight + "px";
	}
}
*/
function setSize(senderwindow, newheight,iframeid) {
	if(newheight < 250) {newheight = 250;}
	var iframes = document.getElementById(iframeid);
	var iframeurl, senderurl, iframeid, senderid;
	senderurl = senderwindow.location.href;
	if (iframes != null)
	{
		iframes.style.height = newheight + "px";
	}
}
