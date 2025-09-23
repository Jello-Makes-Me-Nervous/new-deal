
function validRange() {
  var cartItems = document.getElementById('itemCount').value;
  var i;
  var offerQuantity;
  var hours = parseInt(document.getElementById('offerexpiration').value);
  var setHours = document.getElementById('offerexpiration').value.length;
  var pType = document.getElementById('paymentType').value;
  var pMethod = document.getElementById('paymentMethod').value;

  for (i = 1; i <= cartItems; i++) {
    offerQuantity = parseInt(document.getElementById('offerQuantity'+i).value);
    if (isNaN(offerQuantity)) {
      alert('Please enter an amount in box QTY box '+i);
      return false;
    }

      var minqty = document.getElementById('minqty'+i).value;
      var maxqty = document.getElementById('maxqty'+i).value;
      if (minqty > offerQuantity) {
        alert('The minimum Qty for box #' + i +' is ' + minqty);
        return false;
      }
      if (maxqty < offerQuantity) {
        alert('The maximum Qty for box #' + i +' is ' + maxqty);
        return false;
      }

  };

  if (hours < 24 || hours > 72 || setHours == 0) {
    document.getElementById('offerexpiration').value = '';
    alert('Valid hours MUST BE 24-72');
    return false;
  }

  if (pType === '' &&  pMethod === '') {
    alert('Choose a Payment Method');
    return false;
  }

  if (pMethod.length > 2000) {
    alert('Please limit the message to 2000 characters');
    return false;
  }

  var pTime = document.getElementById('paymentTiming').value;
  if(pTime === '') {
    alert('Choose a Payment Timing');
    return false;
  }

}

function test() {
	alert('HEY are we here??!!!!!');
}

function getAmount(tt) {

    var minqty;
    var maxqty;

    var oCnt = document.getElementById('itemCount');
    var cnt = parseInt(oCnt.value);
    var tot=0;
    var qty;
    var p;
    if (cnt > 0) {
      for(var i=1;i<=cnt;i++){
        qty = parseInt(document.getElementById('offerQuantity'+i).value);
        p = parseFloat(document.getElementById('dprice'+i).value);
        if(qty){
            tot += qty * p;
        }
      }
    }
    document.getElementById('total').value = tot.toFixed(2);
}

function getItemAmounts() {
    var runningOfferTotal = 0;
    //alert('getItemAmounts');

    $('.item_id_value').each(function(indx, item) {
        var thisItemId = $(this).val();
        var thisQtyId = 'offerQuantity'+thisItemId;
        var thisPriceId = 'dprice'+thisItemId;
        //alert('Got itemid '+thisItemId+' '+thisQtyId+' '+thisPriceId);
        if (qtyElement = document.getElementById(thisQtyId)) {
            //alert(thisQtyId+' '+qtyElement.value);
            if (priceElement = document.getElementById(thisPriceId)) {
                var thisTotal = 0;
                //alert(thisPriceId+' '+priceElement.value);
                qty = parseInt(qtyElement.value);
                var priceStr = priceElement.value;
                price = parseFloat(priceStr.replace(',',''));
                //alert(qty+' '+price);
                if(qty && price){
                    thisTotal = qty * price;
                    runningOfferTotal += thisTotal;
                    //alert ('Item '+thisItemId+' '+qtyElement.value+' x '+priceElement.value+' = '+thisTotal+' yields '+runningOfferTotal);
                }
            }
        }
    });
    //alert('Running total '+runningOfferTotal);
    if (runningOfferTotal > 0) {
        /*
        const money_formatter = new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: 'USD',
        });        
        document.getElementById('total').value = money_formatter.format(runningOfferTotal);
        //document.getElementById('total').value = runningOfferTotal).toFixed(2);
        */
        document.getElementById('total').value = runningOfferTotal.toFixed(2);
    } else {
        document.getElementById('total').value = '';
    }
}


function checkCounterOffer(offerItemId) {
    var offerType = document.getElementById('offertype');
    var listingFee = document.getElementById('listingfee');
    var listPrice = document.getElementById('lstdprice_'+offerItemId);
    var offerPrice = document.getElementById('dprice'+offerItemId);
    var listUOM = document.getElementById('uom_'+offerItemId);
    var feeWarning = document.getElementById('feewarning_'+offerItemId);
    
    var dbgmsg = offerItemId;
    if (offerType) { dbgmsg += ' '+offerType.value; }
    if (listingFee) { dbgmsg += ' '+listingFee.value; }
    if (listPrice) { dbgmsg += ' '+listPrice.value; }
    if (offerPrice) { dbgmsg += ' '+offerPrice.value; }
    if (feeWarning) { dbgmsg += ' -'+feeWarning.innerHTML+'-'; }

    //alert(dbgmsg);
    
    var uomMsg = '';
    if (listUOM && listUOM.value) {
        uomMsg = ' / '+listUOM.value;
    }
    
    if (offerType && listingFee && listPrice && offerPrice && feeWarning) {
        if (offerType.value && listingFee.value && listPrice.value && offerPrice.value) {
            var listNumber = Number(listPrice.value);
            var offerNumber = Number(offerPrice.value);
            //alert('ListPrice:'+listNumber+' OfferPrice:'+offerNumber);
            if ( ! (isNaN(listNumber) || isNaN(offerNumber))) {
                if (offerType.value == 'Wanted') {
                    if (listNumber < offerNumber) {
                        //alert('Wanted: Counter offer listing fee of '+listingFee.value+'% applies');
                        feeWarning.innerHTML = 'Entire counter offer will be subject to a '+listingFee.value+'% transaction fee'+'<br />(Listing Price: $'+listPrice.value+uomMsg+')';
                    } else {
                        if (listNumber > offerNumber) {
                            //alert('Wanted: Less / No listing fee applies');
                            feeWarning.innerHTML = '(Listing Price: $'+listPrice.value+uomMsg+')';
                        } else {
                            //alert('Wanted: No listing fee applies');
                            feeWarning.innerHTML = '';
                        }
                    }
                } else {
                    if (offerType.value == 'For Sale') {
                        if (listNumber > offerNumber) {
                            //alert('For Sale: Counter offer listing fee of '+listingFee.value+'% applies');
                            feeWarning.innerHTML = 'Entire counter offer will be subject to a '+listingFee.value+'% transaction fee'+'<br />(Listing Price: $'+listPrice.value+uomMsg+')';
                        } else {
                            if (listNumber < offerNumber) {
                                //alert('Wanted: No listing fee applies');
                                feeWarning.innerHTML = '(Listing Price: $'+listPrice.value+uomMsg+')';
                            } else {
                                //alert('For Sale: No listing fee applies');
                                feeWarning.innerHTML = '';
                            }
                        }
                    }
                }
            } else {
                if (isNaN(offerNumber)) {
                    feeWarning.innerHTML = 'Invalid price entered<br />(Listing Price: $'+listPrice.value+')';
                } else {
                    feeWarning.innerHTML = '';
                }
            }
        }
    }
    return true;
}

function sellerPaymentInfo(selector) {
    var offerType = document.getElementById('offertype');

    if (offerType) {
        //alert('OfferType:'+offerType.value);
        if (offerType.value == 'Wanted') {
            var newSellerIndex = selector.selectedIndex;
            //alert('Selected:'+newSellerIndex);

            var sellerPayment = document.getElementById('sellerpayment');
            if (sellerPayment) {
                if (newSellerIndex > 0) {
                    newSellerIndex--;
                    if (preferredSellerExtras) {
                        if (Array.isArray(preferredSellerExtras)) {
                            //alert('Array size:'+preferredSellerExtras.length);
                            if (preferredSellerExtras.length > newSellerIndex) {
                                var allowInfo = preferredSellerExtras[newSellerIndex][1];
                                var sellerInfo = preferredSellerExtras[newSellerIndex][2];
                                //alert('PT:'+preferredSellerExtras[newSellerIndex][0]+' Allow Info:'+allowInfo+' Seller:'+sellerInfo);
                                if (allowInfo == 'Yes') {
                                    sellerPayment.value = sellerInfo;
                                    $("#sellerpayinfo").show();
                                    $("#sellerextra").val(1);
                                } else {
                                    if (allowInfo == 'Optional') {
                                        sellerPayment.value = sellerInfo;
                                        $("#sellerpayinfo").show();
                                        $("#sellerextra").val(2);
                                    } else {
                                        sellerPayment.value = '';
                                        $("#sellerpayinfo").hide();
                                        $("#sellerextra").val(0);
                                    }
                                }
                                //var sevalue = $("#sellerextra").val();
                                //alert('sellerextra:'+sevalue);
                            } else {
                                alert('Index out of range');
                            }
                        //} else {
                        //    alert('No seller array');
                        }
                    //} else {
                    //    alert('No seller object');
                    }
                //} else {
                //    alert('Seller index less than 0');
                } else {
                    sellerPayment.value = '';
                    $("#sellerpayinfo").hide();
                    $("#sellerextra").value = 0;
                }
            //} else {
            //    alert('No seller payment to update');
            }
        }
    }
    
    return false;
}
