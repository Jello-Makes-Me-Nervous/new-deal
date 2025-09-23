
function validateEFTCashIn() {
    var amt = document.getElementById('amount');
    var success = false;

    if (amt) {
        amountStr = amt.value;
        if (amountStr.length > 0) {
            amount = parseFloat(amountStr.replace(',',''));
            if (amount > 0.00) {
                confirmMsg = "Are you sure you would like to cash in $"+amount+" ?";
                return confirm(confirmMsg);
            } else {
                alert("Amount must be a positive value");
            }
        } else {
            alert("Amount to cash out is requried");
        }
    } else {
        alert("Amount is requried");
    }

    return success;
}

function validateEFTCashOut() {
    var avail = document.getElementById('availablebalance');
    var amt = document.getElementById('amount');
    var success = false;

    if (amt && avail) {
        amountStr = amt.value;
        amount = parseFloat(amountStr.replace(',',''));
        if (amount > 0.00) {
            availableStr = avail.value;
            available = parseFloat(availableStr.replace(',',''));
            if (available > 0.00) {
                if (amount <= available) {
                    confirmMsg = "Are you sure you would like to cash out $"+amount+" ?";
                    return confirm(confirmMsg);
                } else {
                    alert("Amount must be less than or equal to "+available);
                }
            } else {
                alert("Missing available amount");
            }
        } else {
            alert("Amount must be a positive value");
        }
    } else {
        alert("Amount to cash out is requried");
    }

    return success;
}

function validateEFTRedeem(isAdmin=0) {
    var avail = document.getElementById('availablebalance');
    var amt = document.getElementById('amount');
    var fee = document.getElementById('fee');
    var success = false;
/*
    if (avail) {
        alert('Available:'+avail.value);
    }
    if (amt) {
        alert('Amount:'+amt.value);
    }
    if (fee) {
        alert('Fee:'+fee.value);
    }
*/
    if ((amt && avail && fee) || isAdmin) {
        amountStr = amt.value;
        amount = parseFloat(amountStr.replace(',',''));
        if (amount > 0.00) {
            feeStr = fee.value;
            feeAmount = parseFloat(feeStr.replace(',',''));
            totalAmount = amount + feeAmount;
            availableStr = avail.value;
            available = parseFloat(availableStr.replace(',',''));
            if ((available > 0.00) || isAdmin) {
                if ((totalAmount <= available) || isAdmin) {
                    if (feeAmount > 0.00) {
                        confirmMsg = "Are you sure you would like to redeem $"+totalAmount+" less the fee of $"+feeAmount+" for net proceeds of $"+amount+"?";
                    } else {
                        confirmMsg = "Are you sure you would like to redeem $"+totalAmount+" for net proceeds of $"+amount+"?";
                    }
                    return confirm(confirmMsg);
                } else {
                    alert("Amount+Fee must be less than or equal to "+available);
                }
            } else {
                alert("Missing available amount");
            }
        } else {
            alert("Amount must be a positive value");
        }
    } else {
        alert("Amount to redeem is requried");
    }

    return success;
}

function validateEFTTransfer() {
    var amt = document.getElementById('amount');
    var dealerName = document.getElementById('paydealername');

    var success = false;
/*
    if (amt) {
        alert('Amount:'+amt.value);
    }

    if (dealerName) {
        alert('dealerName:'+dealerName.value);
    }
*/
    if (dealerName) {
        dealerNameStr = dealerName.value;
        if (dealerNameStr.length > 0) {
            if (amt) {
                amountStr = amt.value;
                if (amountStr.length > 0) {
                    amount = parseFloat(amountStr.replace(',',''));
                    if (amount > 0.00) {
                        return confirm('Transfer '+amount+' to '+dealerNameStr+' ?');
                    } else {
                        alert("Amount must be a positive value");
                    }
                } else {
                    alert("Amount to transfer is required");
                }
            } else {
                alert("Amount to transfer is required");
            }
        } else {
            alert("Dealer Name is required");
        }
    } else {
        alert("Dealer Name to transfer to is required");
    }

    return success;
}

function changeDate () {

   var month = document.getElementById("mon").value;
   var year =  document.getElementById("year").value;
   document.location.href='?mon='+month+'&year='+year;

}


