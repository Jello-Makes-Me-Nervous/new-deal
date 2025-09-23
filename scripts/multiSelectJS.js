
function sortSelectByValue(selElem, selectedValue) {

     var tmpAry = new Array();
     for (var i=0;i<selElem.options.length;i++) {
       tmpAry[i] = new Array();
       tmpAry[i][0] = selElem.options[i].value;
       tmpAry[i][1] = selElem.options[i].text;
     }
     tmpAry.sort(sortFunction);

     while (selElem.options.length > 0) {
       selElem.options[0] = null;
     }
     for (var i=0;i<tmpAry.length;i++) {
       var op = new Option(tmpAry[i][1], tmpAry[i][0]);
       op.classList.add('multi-select');
       selElem.options[i] = op;
       if (tmpAry[i][0].includes('aaaa',0)) {
         selElem.options[i].disabled = true;
       }
       if (tmpAry[i][0] == selectedValue) {
         selElem.selectedIndex = i;
       }
     }
     selElem.selectedIndex = -1;
     adjustScrollbars(selElem);

      return;
    }

    function sortFunction(a, b) {
       if (a[1] === b[1]) {
           return 0;
       } else {
           return (a[1] < b[1]) ? -1 : 1;
       }
    }

    function selectAssigned(idname) {
     for (var i=0;i<idname.options.length;i++) {
       idname.options[i].selected = 'selected';
     }

   }

    function adjustScrollbars(selElem) {
     if (selElem.options.length <= 15) {
       selElem.classList.add('no-scroll');
     } else {
       selElem.classList.remove('no-scroll');
     }
   }