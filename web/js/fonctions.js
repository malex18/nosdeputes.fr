$(document).ready(function() {
    if (typeof additional_load != 'undefined')
      additional_load();
    // Ajax login
    if (!$('#header_login').attr('value')) {
      $('#header_login').attr('value', 'Identifiant');
    }
    if (!$('#header_pass').attr('value')) {
      $('#header_pass').attr('value', '______________');
    }
	// Menu
	selected = $("a[class='selected']").parent().attr("id");
	retourMenu = 0;
	delayRetourMenu = 100;

	$(".menu_navigation a").mouseover(function() {
	  if(retourMenu) { window.clearTimeout(retourMenu); }
	  $(".menu_navigation a").removeClass("selected");
	  for (i=1; i<=3; i++) { $('#sous_menu_'+i).css("display", "none"); }
	  if ($(this).parent().attr("id") == "item2") { $(this).attr("class", "selected"); $('#sous_menu_1').css("display", "block"); }
	  if ($(this).parent().attr("id") == "item3") { $(this).attr("class", "selected"); $('#sous_menu_2').css("display", "block"); }
	  if ($(this).parent().attr("id") == "item4") { $(this).attr("class", "selected"); $('#sous_menu_3').css("display", "block"); }
	});
	
	function setOriginalMenu() {
	  $(".menu_navigation a").removeClass("selected");
	  for (i=1; i<=3; i++) { $('#sous_menu_'+i).css("display", "none"); }
	  if (selected == "item2") { $("#item2 a").attr("class", "selected"); $('#sous_menu_1').css("display", "block"); }
	  if (selected == "item3") { $("#item3 a").attr("class", "selected"); $('#sous_menu_2').css("display", "block"); }
	  if (selected == "item4") { $("#item4 a").attr("class", "selected"); $('#sous_menu_3').css("display", "block"); }
	}
	
	$("#sous_menu_1, #sous_menu_2, #sous_menu_3").mouseover(function() {
	  if(retourMenu) { window.clearTimeout(retourMenu); }
	});
		
	$(".menu_navigation a, #sous_menu_1, #sous_menu_2, #sous_menu_3").mouseout(function() {
	  if(retourMenu) { window.clearTimeout(retourMenu); }
	  retourMenu = window.setTimeout(setOriginalMenu, delayRetourMenu);
	});
	
	// Effet survol tagcloud
	$(".internal_tag_cloud").prepend("<div id=\"loupe\"></div>");
	
	$(".internal_tag_cloud a").each(
	  function() { 
	    $(this).attr("alt", $(this).attr('title'));
	    $(this).removeAttr('title');
	    $(this).mouseover(function() { 
	      $("#loupe").text($(this).text()+" ("+$(this).attr("alt")+")");
	      $("#loupe").css("display", "block");
	    });
	  }
	);
	$(".internal_tag_cloud").mousemove(function(e) {
	  $("#loupe").css({ left: e.pageX - 150, top: e.pageY - 155});
	});
	
	$(".internal_tag_cloud a").mouseout(function() {
	  $("#loupe").css("display", "none");
	});
	
	
  }); // fin document ready

// Google
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
try {
  var pageTracker = _gat._getTracker("UA-10423931-2");
  pageTracker._trackPageview();
} catch(err) {}