function validAlerts() {
  var catId = document.getElementById('categoryId').value;
  if(catId == 0) {
    alert('Please choose a Category');
    return false;
  }
  var subCat = document.getElementById('subCatId').value;
  if(subCat == 0) {
    alert('Please choose a Sub-Category');
    return false;
  }
  var yr = document.getElementById('year').value;
  if(yr === '') {
    alert('Please input a year ');
    return false;
  }
  var boxId = document.getElementById('boxTypeId').value;
  //alert(boxId);
  if(boxId == 0) {
    alert('Please choose a Box Type');
    return false;
  }

}

function yearBox() {
console.log('here');
  var id = document.getElementById('categoryId').value;
console.log(id);
  var yeartype = categoryType.filter(function(item){ return item[0] == id; });
  var type = yeartype[0][1];
console.log(yeartype[0][1]);
  if (type == 1) {
  	document.getElementById('years').innerHTML = "<strong>Years:</strong>";
	document.getElementById('yearin').innerHTML = "<input type='text' name='year' id='year1' placeholder='YY/Y' size='4' onblur='checkYear1()'>";
  }
  if (type == 2) {
  	document.getElementById('years').innerHTML = "<strong>Years:</strong>";
  	document.getElementById('yearin').innerHTML = "<input type='text' name='year' id='year2' placeholder='YYYY' size='4' onblur='checkYear2()'>";
  }

}

