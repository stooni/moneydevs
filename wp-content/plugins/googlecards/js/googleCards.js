window.onload=function()
{
	var credit = document.getElementById('plusCardCredit');
	credit.style.display = "none";
}
function togglePlusCredit()
{
	var credit = document.getElementById("plusCardCredit");
	if (credit.style.display == "none")
	{
		credit.style.display = "block";
	}
	else
	{
		credit.style.display = "none";
	}
}