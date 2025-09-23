
function validateCats() {
  var categoryName = trimstr(document.getElementById('categoryname').value);
  var categoryDescription = trimstr(document.getElementById('categorydescription').value);
  var categoryTypeId = trimstr(document.getElementById('categorytypeid').value);
  var yearFormatTypeId = trimstr(document.getElementById('yearformattypeid').value);
  //find strip spaces

  if (categoryName.length < 1) {
    alert('Please input a Name');
    return false;
  }
  if (categoryDescription.length < 1) {
    alert('Please input a Description');
    return false;
  }
  if (categoryTypeId == 0) {
    alert('Please choose a Category Type');
    return false;
  }
  if (yearFormatTypeId == 0) {
    alert('Please choose a Year Format');
    return false;
  }

  return true;

}
