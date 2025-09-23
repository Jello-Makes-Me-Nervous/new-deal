var xmlhttp_obj = false;
var counter = 0;

//create the XMLHttpRequest
function ajax_xmlhttp(){
    if (window.XMLHttpRequest){ // if Mozilla, Safari etc
      xmlhttp_obj = new XMLHttpRequest();
    }else if (window.ActiveXObject){ // if IE
      try{
        xmlhttp_obj = new ActiveXObject("Msxml2.XMLHTTP");
      }catch (e){
        try{
          xmlhttp_obj = new ActiveXObject("Microsoft.XMLHTTP");
        }catch (e){
        }
      }
    }else{
      xmlhttp_obj = false;
    }
    return xmlhttp_obj;
}   //get content via GET

function ajax_getcontent(url, containerid, msg){
//    alert(url);
    var urlredo = url.replace(/&amp;/g,'&');
//    alert(urlredo);
    var xmlhttp_obj = ajax_xmlhttp();
    if (msg.length > 0) {
        document.getElementById(containerid).innerHTML = msg;
    } else {
        document.getElementById(containerid).innerHTML = 'Please wait loading latest data...';
    }
    xmlhttp_obj.onreadystatechange=function(){
      ajax_loadpage(xmlhttp_obj, containerid, url);
    }
    xmlhttp_obj.open('GET', urlredo, true);
    xmlhttp_obj.send(null);
}


function ajax_loadpage(xmlhttp_obj, containerid, url){
    var errorMsg = "";
    if ( xmlhttp_obj.readyState == 4) {
        if (xmlhttp_obj.status == 200 ){
            document.getElementById(containerid).innerHTML = xmlhttp_obj.responseText;
        } else if (xmlhttp_obj.status >= 400) {
            errorMsg = "<span style='color:red;'>An error occured while retreiving the latest information.<BR>Please contact DealernetX technical suppport for assistance.";
            errorMsg = errorMsg + "(" + xmlhttp_obj.readyState + ":" + xmlhttp_obj.status + ")<span>";
            errorMsg = errorMsg + "<BR><!-- [" + url + "] -->";
            document.getElementById(containerid).innerHTML = errorMsg;
        } else {
//        errorMsg = "(" + xmlhttp_obj.readyState + ":" + xmlhttp_obj.status + ":" + counter.toString() + ")<span>";
          document.getElementById(containerid).innerHTML = document.getElementById(containerid).innerHTML + "...";
        }
    }
}