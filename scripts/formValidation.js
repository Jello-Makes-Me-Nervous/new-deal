function ltrim(argvalue) {

 while (1) {
   if (argvalue.substring(0, 1) != " ")
     break;
   argvalue = argvalue.substring(1, argvalue.length);
 }

 return argvalue;
}

function rtrim(argvalue) {

 while (1) {
   if (argvalue.substring(argvalue.length - 1, argvalue.length) != " ")
     break;
   argvalue = argvalue.substring(0, argvalue.length - 1);
 }

 return argvalue;
}

function trim(argvalue) {
  var tmpstr = ltrim(argvalue);
  return rtrim(tmpstr);
  }

function isNull( val ) {
	var isValid = false;

 	if (val+"" == "null")
 		isValid = true;

	return isValid;
}  // end isNull

function isUndef( val ) {
	var isValid = false;

 	if (val+"" == "undefined")
 		isValid = true;

	return isValid;
}  // end isUndef

function isAlpha( str ) {
	// Return immediately if an invalid value was passed in
	if (str+"" == "undefined" || str+"" == "null" || str+"" == "")
		return false;

	var isValid = true;

	str += "";	// convert to a string for performing string comparisons.

	// Loop through string one character at time,  breaking out of for
	// loop when an non Alpha character is found.
  	for (i = 0; i < str.length; i++) {
		// Alpha must be between "A"-"Z", or "a"-"z"
		if ( !( ((str.charAt(i) >= "a") && (str.charAt(i) <= "z")) ||
      			((str.charAt(i) >= "A") && (str.charAt(i) <= "Z")) ) ) {
         				isValid = false;
         				break;
      			}
   } // end for loop

	return isValid;
}  // end isAlpha

function isEmpty( str ) {
	var isValid = false;

 	if ( isNull(str) || isUndef(str) || (str+"" == "") )
 		isValid = true;

	return isValid;
}  // end isEmpty

function isEmailValid( str ) {
	// Return immediately if an invalid value was passed in
	if (str+"" == "undefined" || str+"" == "null" || str+"" == "")
		return false;

	var isValid = true;

	str += "";

	namestr = str.substring(0, str.indexOf("@"));  // everything before the '@'
	domainstr = str.substring(str.indexOf("@")+1, str.length); // everything after the '@'

	// Rules: namestr cannot be empty, or that would indicate no characters before the '@',
	// domainstr must contain a period that is not the first character (i.e. right after
	// the '@').  The last character must be an alpha.
   	if (isEmpty(str) || (namestr.length == 0) ||
			(domainstr.indexOf(".") <= 0) ||
			(domainstr.indexOf("@") != -1) ||
			!isAlpha(str.charAt(str.length-1)))
		isValid = false;

   	return isValid;
} // end isValidEmail

function isTextAcceptable (s){
    var i = 0;
    var sLength = s.length;

// Check for CR and LF and allow them
// otherwise alert is '<' or '>' or any
// other non-printable character.

    while (i < sLength){
      c = s.charAt(i);
      if ((isNaN(c)== true)){
        if ((c == "<") || (c == ">") ||
            (c < " " ) || (c > "~")){
          i = sLength;
          return false;
        }
      }
      i++;
    }
    return true;
}

function isCheckboxChecked( checkboxObject ) {

	// Validate parameter value
	if (checkboxObject+"" == "undefined" || checkboxObject == null)
		return false;

	for (var i=0; i < checkboxObject.length; i++) {
		if (checkboxObject[i].checked) {
			return true;
		}
	} // end for loop
	return false;
}


function isRadioChecked( radioObject ) {

	// Validate parameter value
	if (radioObject+"" == "undefined" || radioObject == null)
		return false;

	for (var i=0; i < radioObject.length; i++) {
		if (radioObject[i].checked) {
			return true;
		}
	} // end for loop
	return false;
}


function isSelected( selectObject ) {

	// Validate parameter value
	if (selectObject+"" == "undefined" || selectObject == null)
		return false;

    // ignore first item
	for (var i=0; i < selectObject.length; i++) {
		if (selectObject.options[i].selected) {
			return true;
		}
	} // end for loop
	return false;
}

function isTextAcceptable (s){
    var i = 0;
    var sLength = s.length;

    while (i < sLength){
      c = s.charAt(i);
      if ((isNaN(c)== true)){
        if ((c == "<") || (c == ">") ||
            (c < " " ) || (c > "~")){
          i = sLength;
          return false;
        }
      }
      i++;
    }
    return true;
}

function isUSZip(s) {
    var pattern = /^\d{5}(-\d{4})?$/;
    return s.match(pattern);
}
function isCanadaZip(s) {
    var pattern = /^[A-Z]\d[A-Z][ ]?\d[A-Z]\d$/;
    return s.match(pattern);
}
function isZip(s) {
    return isUSZip(s) || isCanadaZip(s);
}

function isPosInteger(s) {
    var pattern = /^(0*[1-9][0-9]*)$/;
    return s.match(pattern);
}

function isInteger(s) {
    var pattern = /^(0|[+-]?[1-9][0-9]*)$/;
    return s.match(pattern);
}

function isFloat(s) {
    var pattern = /^[+-]?([1-9]\d*|([1-9]\d*|0)?\.\d*)$/;
    return s.match(pattern);
}

function isHtmlSafe(s) {
   // exclude angle brackets
   var pattern = /^[^<>]*$/;
   return s.match(pattern);
}

function isText(s, max) {
    return (s.length <= max);
}

// checks for single or multiple ";" seperated addresses.
function isEmail(s) {
  var isValid = true
  var badpattern = /\;{2,}/
  if(s.match(badpattern)){ // check for multiple ";" with no text between them.
    return (isValid=false)
  }
  var pattern = /^\w[\w\.\-]*@(\w[\w\-]*\.)+(\w[\w\-]*)*[a-zA-Z]$/
  if (s.indexOf(";") >=0 ) { // multiple addresses
    s=s.split(";")
    for(var j=0;j<s.length;j++){
      if(s[j].length > 0){
        if (!s[j].match(pattern)){
          isValid = false
        }
      }
    }
    return isValid
  }else{// only one address
    return s.match(pattern);
  }
}

function isDate(s) {
    var pattern = /^\d{1,2}\/\d{1,2}\/(\d{2}|\d{4})$/
    return s.match(pattern);
}

function isOrderItem(s) {
    var pattern = /^\d+(-\d+)?$/
    return s.match(pattern);
}

function VerifyTextField(field, name, type, required, max) {

   if (trim(field.value) == "") {
      if (required)
         return (name + ": Required\n");
      return "";
   }
   if (type == "text" && !isText(field.value,max))
      return (name + ": More than " + max + " characters\n");
   else if (type == "nonhtml" && !isHtmlSafe(field.value))
      return (name + ": Angle brackets are not allowed\n");
   else if (type == "nonhtml" && !isText(field.value,max))
      return (name + ": More than " + max + " characters\n");
   else if (type == "integer" && !isInteger(field.value))
      return (name + ": Not a number\n");
   else if (type == "float" && !isFloat(field.value))
      return (name + ": Not a floating point number\n");
   else if (type == "email" && !isEmail(field.value))
      return (name + ": Not a valid email address\n");
   else if (type == "date" && !isDate(field.value))
      return (name + ": Not a valid date\n");
   else if (type == "zip" && !isZip(field.value))
      return (name + ": Not a valid zip code\n");
   return "";
}

function VerifyAllFields(f,a) {

    m = "";
    for (i=0; i<f.elements.length; i++)
        for (j=0; j<a.length; j++)
           if (f.elements[i].name.match(a[j][0])) {
               m = m + VerifyTextField(f.elements[i], a[j][1], a[j][2], a[j][3], a[j][4]);
               break;
           }

    if (m != "") {
        alert("The following fields contain values that are not permitted:\n\n" + m);
        return false;
    }

    return true;
}
