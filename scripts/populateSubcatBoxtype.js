function isGaming(catid) {
    var cattype = boxtypedata.filter(function(item){ return item[0] == catid; });
    if (cattype[0][3] == 4) {
      return true;
    }
    return false;
//categorytypeid = 4 is gaming no year
}

  function populateBoxType(catid) {
console.log('BOX');
console.log(catid);

    var boxtype = boxtypedata.filter(function(item){ return item[0] == catid; });
    var oBoxType = document.getElementById('boxTypeId');
    console.log(oBoxType);
    while (oBoxType.options.length > 0) {
      oBoxType.remove(0);
    }
    var op = new Option('Select', 0);
    oBoxType.options[0] = op;
    for(var i = 0; i < boxtype.length; i++){
      oBoxType.add(new Option(boxtype[i][2], boxtype[i][1]));
    }
  }

  function populateSubCat(catid) {
console.log('CAT');
console.log(catid);
console.log(subcatdata.length);
    var subcat = subcatdata.filter(function(item){ return item[0] == catid; });
    var oSubCat = document.getElementById('subCategoryId');
    while (oSubCat.options.length > 0) {
      oSubCat.remove(0);
    }
    var op = new Option('Select', 0);
    oSubCat.options[0] = op;
    for(var i = 0; i < subcat.length; i++){
      oSubCat.add(new Option(subcat[i][3], subcat[i][2]));
    }
  }
