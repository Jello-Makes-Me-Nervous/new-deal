
function validateCC() {
    var ccnumber    = document.getElementById('cardnumber').value;
    var expmonth    = $('#expiremonth').find(":selected").val();
    var expyear     = $('#expireyear').find(":selected").val();
    var cvv         = document.getElementById('cvv').value;

    if (validateLuhnAlgorithm(ccnumber)){
        if ((!isNaN(expmonth)) && (!isNaN(expyear)) &&
            validateExpirationDate(parseInt(expmonth), parseInt(expyear))){
            if (validateCVV(cvv)){
                return true;
            } else {
                alert('Invalid CVV');
                return false;
            }
        } else {
            alert('Invalid expiration date');
            return false;
        }
    } else {
        alert('Invalid credit card number');
        return false;
    }

    return false;
}


// Luhn Algorithm
function validateLuhnAlgorithm(cardNumber) {
    let sum = 0;
    let isEven = false;

    for (let i = cardNumber.length - 1; i >= 0; i--) {
        let digit = parseInt(cardNumber.charAt(i), 10);

        if (isEven) {
            digit *= 2;
            if (digit > 9) {
                digit -= 9;
            }
        }
        sum += digit;
        isEven = !isEven;
    }
    detectCardType(cardNumber);
    return sum % 10 === 0;
}


// Card Type Detection
function detectCardType(cardNumber) {
    const patterns = {
        visa: /^4[0-9]{12}(?:[0-9]{3})?$/,
        mastercard: /^5[1-5][0-9]{14}$/,
        amex: /^3[47][0-9]{13}$/,
        discover: /^6(?:011|5[0-9]{2})[0-9]{12}$/,
    };

    for (const cardType in patterns) {
        if (patterns[cardType].test(cardNumber)) {
            return cardType;
        }
    }

    return "Unknown";
}


// Expiration Date Validation
function validateExpirationDate(expirationMonth, expirationYear) {
    const currentDate = new Date();
    const currentYear = currentDate.getFullYear();
    const currentMonth = currentDate.getMonth() + 1; // January is 0

    if (expirationYear > currentYear) {
        return true;
    } else if (expirationYear === currentYear && expirationMonth >= currentMonth) {
        return true;
    }

    return false;
}

// CVV/CVC Validation
function validateCVV(cvv) {
    const cvvPattern = /^[0-9]{3,4}$/;
    return cvvPattern.test(cvv);
}
