var toasting = {
    gettoaster: function() {
        var toaster = $('#' + defaults.toaster.id);

        if (toaster.length < 1) {
            toaster = $(defaults.toaster.template).attr('id', defaults.toaster.id).css(defaults.toaster.css).addClass(defaults.toaster['class']);

            if ((defaults.stylesheet) && (!$("link[href=" + defaults.stylesheet + "]").length)) {
                $('head').appendTo('<link rel="stylesheet" href="' + defaults.stylesheet + '">');
            }

            $(defaults.toaster.container).append(toaster);
        }

        return toaster;
    },

    notify: function(message, priority, timeout) {
        var $toaster = this.gettoaster();
        var $toast = $(defaults.toast.template.replace('%priority%', priority)).hide().css(defaults.toast.css).addClass(defaults.toast['class']);

        if (priority == 'success') {
          var title = text_success;
        } else {
          var title = text_warning;
        }

        $('.title', $toast).css(defaults.toast.csst).html(title);
        $('.message', $toast).css(defaults.toast.cssm).html(message);

        if ((defaults.debug) && (window.console)) {
            console.log(toast);
        }

        $toaster.append(defaults.toast.display($toast));

        if (defaults.donotdismiss.indexOf(priority) === -1) {
            if (timeout == null) {
                var timeout = (typeof defaults.timeout === 'number') ? defaults.timeout : ((typeof defaults.timeout === 'object') && (priority in defaults.timeout)) ? defaults.timeout[priority] : 1500;
            };
            setTimeout(function() {
                defaults.toast.remove($toast, function() {
                    $toast.remove();
                });
            }, timeout);
        }
    }
};

var defaults = {
    'toaster': {
        'id': 'toaster',
        'container': 'body',
        'template': '<div></div>',
        'class': 'toaster',
        'css': {
            'position': 'fixed',
            'zIndex': 1051
        }
    },

    'toast': {
        'template': '<div class="alert alert-%priority%" role="alert">' + '<button type="button" class="close" data-dismiss="alert">' + '<span aria-hidden="true">&times;</span>' + '<span class="sr-only">Close</span>' + '</button>' + '<span class="title"></span>: <span class="message"></span>' + '</div>',

        'css': {},
        'cssm': {},
        'csst': {
            'fontWeight': 'bold'
        },

        'fade': 'slow',

        'display': function($toast) {
            return $toast.fadeIn(defaults.toast.fade);
        },

        'remove': function($toast, callback) {
            return $toast.animate({
                opacity: '0',
                padding: '0px',
                height: '0px'
            }, {
                duration: defaults.toast.fade,
                complete: callback
            });
        }
    },

    'debug': false,
    'timeout': 6000,
    'stylesheet': null,
    'donotdismiss': []
};

$.toaster = function(options) {
    if (typeof options === 'object') {
        if ('settings' in options) {
            settings = $.extend(settings, options.settings);
        }

        var message = ('message' in options) ? options.message : null;
        var priority = ('priority' in options) ? options.priority : 'info';
        var timeout = ('timeout' in options) ? options.timeout : null;

        if (message !== null) {
            toasting.notify(message, priority, timeout);
        }
    }
};

var pospb_defaults = {
    'pospb': {
        'id': 'pospb',
        'container': 'body',
        'template': '<div></div>',
        'class': 'posProductBlock',
        'css': {
            'position': 'fixed',
            'zIndex': 998
        }
    },

    'post': {
        'template': '<img src="%image_path%" width="50" height="50">',

        'remove': function($post, callback) {
            return $post.animate({
                opacity: '0',
                padding: '0px',
                margin: '0px',
                height: '0px'
            }, {
                duration: 500,
                complete: callback
            });
        }
    },

    'timeout': 500
};

$.posting = function(options) {
    var $poster = $(pospb_defaults.pospb.template).css(pospb_defaults.pospb.css).addClass(pospb_defaults.pospb.class);
    $(pospb_defaults.pospb.container).append($poster);
    var $post = $(pospb_defaults.post.template.replace('%image_path%', options.image_path));

    $poster.append($post);

    var cart_offset = $('.cart-total').offset();
    var cart_top = cart_offset.top - $(window).scrollTop();
    var cart_left = cart_offset.left - $(window).scrollLeft();

    $poster.css({
      left: options.product_left,
      top: options.product_top,
      display: 'block'
    }).animate({left: cart_left,top: cart_top}, 500);

    setTimeout(function() {
        pospb_defaults.post.remove($post, function() {
            $post.remove();
        });
    }, pospb_defaults.timeout);
}

// Offline cart add remove functions
var cart_product = {
  'add': function(product_index, quantity, option, thisthis, by_barcode) {
    var cart_options = {};
    var option_count = 0, option_price = 0;
    if (option) {
      var product_options = pos_products[product_index]['options'];
      for (var i = 0; i < product_options.length; i++) {
        var option_val = false;
        if (product_options[i]['type'] == 'select' || product_options[i]['type'] == 'textarea') {
          var option_name = product_options[i]['type'] + '[name="option[' + product_options[i]['product_option_id'] + ']"]';
        } else if (product_options[i]['type'] == 'radio' || product_options[i]['type'] == 'image') {
          var option_name = 'input[name="option[' + product_options[i]['product_option_id'] + ']"]:checked';
        } else if (product_options[i]['type'] == 'checkbox') {
          var option_name = 'input:checkbox[name="option[' + product_options[i]['product_option_id'] + '][]"]:checked';
          option_val = [];
          $(option_name).each(function(){
              option_val.push($(this).val());
          });
        } else {
          var option_name = 'input[name="option[' + product_options[i]['product_option_id'] + ']"]';
        };

        if (!option_val) {
          option_val = $(option_name).val();
        }

        if (option_val == 'undefined' || option_val == '' || option_val == 'null' || option_val == null) {
          option_val = '';
        };

        //Add function for check date formate or note
          if (option_val != '' && product_options[i]['type']=='date') {

               var regex=/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/;
              if (!regex.test(option_val)) {
                $.toaster({
                  priority: 'danger',
                  message: text_option_required_date,
                  timeout: 5000
                });
                return;
              }

          }

          //end code date formate check

        if (product_options[i]['required'] == 1 && option_val == '') {
          $.toaster({
            priority: 'danger',
            message: text_option_required,
            timeout: 5000
          });
          return;
        } else {
          var product_option = {};
          product_option.product_option_id = product_options[i]['product_option_id'];
          product_option.value = option_val;
          product_option.option = '';
          if (product_options[i]['product_option_value'].length) {
            for (var j = 0; j < product_options[i]['product_option_value'].length; j++) {
              if((product_options[i]['product_option_value'][j]['product_option_value_id'] == option_val)) {
                product_option.option = product_options[i]['product_option_value'][j]['name'];
                if (product_options[i]['product_option_value'][j]['price_prefix'] == '-') {
                  option_price -= Number(product_options[i]['product_option_value'][j]['uf']);
                } else {
                  option_price += Number(product_options[i]['product_option_value'][j]['uf']);
                }
                break;
              } else if (Array.isArray(option_val) && $.inArray(product_options[i]['product_option_value'][j]['product_option_value_id'], option_val) != -1) {
                if (product_option.option == '') {
                  product_option.option += product_options[i]['product_option_value'][j]['name'];
                } else {
                  product_option.option += ', ' + product_options[i]['product_option_value'][j]['name'];
                }
                if (product_options[i]['product_option_value'][j]['price_prefix'] == '-') {
                  option_price -= Number(product_options[i]['product_option_value'][j]['uf']);
                } else {
                  option_price += Number(product_options[i]['product_option_value'][j]['uf']);
                }
              }
            };
            if (product_option.option == undefined) {
              product_option.option = '';
            };
          } else {
            product_option.option = option_val;
          };
          cart_options[option_count] = product_option;
          option_count++;
        };
      };
    }

    product_name = pos_products[product_index]['name'];
    product_id = pos_products[product_index]['product_id'];

    var skip = false;
    var cart_changed = false;

    if (pos_products[product_index]['quantity'] < 1) {
        $.toaster({
          priority: 'danger',
          message: text_no_quantity.replace('%product-name%', product_name),
          timeout: 2500
        });
        skip = true;
    };

    // var current_cart_length = Object.keys(pos_cart[current_cart]).length;

    for (var i = 0; i < Object.keys(pos_cart[current_cart]).length; i++) {
        cart_options_string = JSON.stringify(pos_cart[current_cart][i]['options']);
        cart_options_string1 = JSON.stringify(cart_options);

        if ((pos_cart[current_cart][i]['product_id'] == product_id) && (cart_options_string == cart_options_string1)) {
            if (pos_products[product_index]['quantity'] < (pos_cart[current_cart][i]['quantity'] + 1)) {
                $.toaster({
                  priority: 'danger',
                  message: text_no_quantity.replace('%product-name%', product_name),
                  timeout: 2500
                });
                skip = true;
                break;
            };
            pos_cart[current_cart][i]['quantity'] += 1;
            pos_cart[current_cart][i]['uf_total'] =  parseFloat(pos_cart[current_cart][i]['quantity'] * pos_cart[current_cart][i]['uf']).toFixed(2);
            if (symbol_position == 'L') {
                pos_cart[current_cart][i]['total'] = currency_code + pos_cart[current_cart][i]['uf_total'];
            } else {
                pos_cart[current_cart][i]['total'] = pos_cart[current_cart][i]['uf_total'] + currency_code;
            }
            cart_changed = true;
            skip = true;
            break;
        }
    };

    if (skip == false) {
        product_price = pos_products[product_index]['price'];
        special_price = pos_products[product_index]['special'];
        if (option_price) {
          special_price = 0;
          option_price = removeTax(product_index, option_price);
        }
        uf_price = pos_products[product_index]['price_uf'] + option_price;
        product_quantity = 1;
        uf_product_total = (product_quantity * uf_price);
        uf_product_total = parseFloat(uf_product_total).toFixed(2);
        if (symbol_position == 'L') {
            product_total = currency_code + uf_product_total;
            if (option_price) {
              product_price = currency_code + parseFloat(uf_price).toFixed(2);
            }
        } else {
            product_total = uf_product_total + currency_code;
            if (option_price) {
              product_price = parseFloat(uf_price).toFixed(2) + currency_code;
            }
        }

        var new_product = {name: product_name, price: product_price, special: special_price, uf: uf_price, product_id: product_id, total: product_total, quantity: product_quantity, uf_total: uf_product_total, options: cart_options, remove: pos_remove_id, product_index: product_index};
        pos_remove_id++;
        if (localStorage) {
            localStorage.pos_remove_id = pos_remove_id;
        };
        var current_cart_elements = Object.keys(pos_cart[current_cart]).length;
        pos_cart[current_cart][current_cart_elements] = new_product;
        cart_changed = true;
    };

    cartLocalStorage();
    updateCart();

    if (cart_changed == true) {
      $("#barcodeScan").modal('hide');
      var product_image = pos_products[product_index]['image'];

      var product_offset = $(thisthis).offset();
      var product_top = product_offset.top - $(window).scrollTop();
      var product_left = product_offset.left - $(window).scrollLeft();

      $.posting({
        image_path: product_image,
        product_left: product_left,
        product_top: product_top
      });

      $.toaster({
        priority: 'success',
        message: text_product_added.replace('%product-name%', product_name),
        timeout: 1500
      });

      if (option) {
        $('.in').trigger('click');
        if (by_barcode) {
          $('.barcode-scan').trigger('click');
        }
      }
    } else {
      $.toaster({
        priority: 'warning',
        message: text_product_not_added.replace('%product-name%', product_name),
        timeout: 2000
      });
    }
  },
  'remove': function(key, auto) {
    if (auto) {
      var confirm_remove = true;
    } else {
      var confirm_remove = confirm(text_sure);
    }

    if (confirm_remove) {
      var current_cart_length = Object.keys(pos_cart[current_cart]).length;
      if (current_cart_length) {
        var removed = 0;
        for (var l = 0; l < current_cart_length; l++) {
          if (key == pos_cart[current_cart][l]['remove']) {
            removed = 1;
            $.toaster({
              priority: 'success',
              message: text_product_removed.replace('%product-name%', pos_cart[current_cart][l]["name"]),
              timeout: 3000
            });
          };
          if (removed) {
            pos_cart[current_cart][l] = pos_cart[current_cart][l + 1];
          };
        };
        delete pos_cart[current_cart][current_cart_length - 1];

        cartLocalStorage();
        updateCart();
      };
    }
  },
  'update': function(type, key, product_index) {
    var current_cart_length = Object.keys(pos_cart[current_cart]).length;
    if (current_cart_length) {
      for (var l = 0; l < current_cart_length; l++) {
        if (key == pos_cart[current_cart][l]['remove']) {
            if (type) {
                if (pos_products[product_index] && pos_products[product_index]['quantity'] < (pos_cart[current_cart][l]['quantity'] + 1)) {
                    $.toaster({
                        priority: 'danger',
                        message: text_no_quantity.replace('%product-name%', pos_products[product_index]["name"]),
                        timeout: 2500
                    });
                    skip = true;
                    break;
                };
                pos_cart[current_cart][l]['quantity'] += 1;
            } else {
                if (pos_cart[current_cart][l]['quantity'] == 1) {
                    cart_product.remove(key, true);
                    break;
                };
                pos_cart[current_cart][l]['quantity'] -= 1;
            };
            pos_cart[current_cart][l]['uf_total'] = parseFloat(pos_cart[current_cart][l]['quantity'] * pos_cart[current_cart][l]['uf']).toFixed(2);
            if (symbol_position == 'L') {
                pos_cart[current_cart][l]['total'] = currency_code + pos_cart[current_cart][l]['uf_total'];
            } else {
                pos_cart[current_cart][l]['total'] = pos_cart[current_cart][l]['uf_total'] + currency_code;
            }
            break;
        };
      };

      cartLocalStorage();
      updateCart();
    };
  }
}

var cart_list = {
  add: function (note) {
    pos_holds[current_cart] = {};
    if (note) {
      var tmp_div = document.createElement("DIV");
      tmp_div.innerHTML = note;
      note = tmp_div.textContent || tmp_div.innerText || "";
      pos_holds[current_cart]['note'] = note;
    } else {
      pos_holds[current_cart]['note'] = '';
    }
    pos_holds[current_cart]['date'] = getCurrentDate();
    pos_holds[current_cart]['time'] = getCurrentTime();
    pos_holds[current_cart]['customer_id'] = customer_id;
    pos_holds[current_cart]['customer_name'] = customer_name;
    customer_id = 0;
    customer_name = '';
    $('#customer-name').text(text_customer_select);

    var all_carts = Object.keys(pos_cart);
    var total_carts = all_carts.length;
    var last_cart = parseInt(all_carts[parseInt(total_carts - 1)]);
    var new_cart = last_cart + 1;
    pos_cart[new_cart] = {};
    current_cart = new_cart;
    all_carts = Object.keys(pos_cart);
    cartLocalStorage();
    showAllCarts();
    printCart();
    $.toaster({
      priority: 'success',
      message: text_cart_add,
      timeout: 3000
    });
    $('.cart-hold').text(total_carts);
  },
  delete: function (toastIt, cart_id) {
    if (!toastIt) {
      var confirm_delete = true;
    } else {
      var confirm_delete = confirm(text_sure);
    }

    if (confirm_delete) {
      uf_total_discount = 0;
      var total_cart_count = Object.keys(pos_cart).length;
      if (total_cart_count <= 1) {
        delete pos_cart[current_cart];
        current_cart = 0;
        pos_cart[current_cart] = {};
        if (toastIt) {
          $.toaster({
            priority: 'warning',
            message: text_cart_empty,
            timeout: 2000
          });
        }
        cartLocalStorage();
        showAllCarts();
        printCart();
        return;
      };

      if (cart_id != undefined) {
        delete pos_cart[cart_id];
        delete pos_holds[cart_id];
        if (toastIt) {
          $.toaster({
            priority: 'success',
            message: text_cart_deleted,
            timeout: 2000
          });
        };
      } else {
        delete pos_cart[current_cart];
        delete pos_holds[current_cart];
        var all_carts = Object.keys(pos_cart);
        var total_carts = all_carts.length;
        current_cart = parseInt(all_carts[parseInt(total_carts - 1)]);
        if (toastIt) {
          $.toaster({
            priority: 'success',
            message: text_current_deleted,
            timeout: 2000
          });
        };
      }
      cartLocalStorage();
      showAllCarts();
      printCart();
      $('.cart-hold').text(total_cart_count - 2);
    }
  },
  select: function (key) {
    $('#loader').css('display', 'block');
    uf_total_discount = 0;
    setTimeout(function () {
      if (!(current_cart == key)) {
        if (Object.keys(pos_cart[current_cart]).length) {
          pos_holds[current_cart] = {};
          pos_holds[current_cart]['note'] = '';
          pos_holds[current_cart]['date'] = getCurrentDate();
          pos_holds[current_cart]['time'] = getCurrentTime();
          pos_holds[current_cart]['customer_id'] = customer_id;
          pos_holds[current_cart]['customer_name'] = customer_name;
        } else {
          cart_list.delete(0);
        }

        current_cart = key;
        if (pos_holds[current_cart]['customer_name']) {
          customer_id = pos_holds[current_cart]['customer_id'];
          customer_name = pos_holds[current_cart]['customer_name'];
          $('#customer-name').text(customer_name);
        } else {
          customer_id = 0;
          customer_name = '';
          $('#customer-name').text(text_customer_select);
        }
        $('#current-cart').text(parseInt(current_cart) + 1);
        cartLocalStorage();
        printCart();
        $.toaster({
          priority: 'success',
          message: text_another_cart,
          timeout: 2000
        });
      };

      $('.wkorder:nth-child(2)').trigger('click');
      $('#loader').css('display', 'none');
      $('#upper-cart').slideUp();
      $('.wksidepanel').removeClass('onfocus');
      $('.order-parent').removeClass('panel-show');
      $('.sidepanel').removeClass('sidepanel-show');
    }, 500);
  }
}

function updateCart() {
  coupon = '';
  coupon_disc = 0;
  if (discountApply) {
    printCart(0);
    setTimeout(function () {
      $('#addDiscountbtn').trigger('click');
    }, 50);
  } else {
    printCart();
  }
}

function printCart (noupdate) {
  if (Object.keys(pos_cart).length) {
    var current_cart_length = Object.keys(pos_cart[current_cart]).length;
  } else {
    var current_cart_length = 0;
  }
  var cart_quantity = 0;
  cart_tax = 0;
  var cart_html = '';
  uf_cart_total = 0;
  uf_sub_total = 0;
  showAllCarts();

  for (var i = 0; i < current_cart_length; i++) {
    cart_quantity += pos_cart[current_cart][i]['quantity'];

    if ((tax_status != 0) && (pos_cart[current_cart][i]['product_index'] != 0)) {
      var product_tax = calculateTax(pos_cart[current_cart][i]['product_id'], pos_cart[current_cart][i]['uf']);
      cart_tax += parseFloat(product_tax * pos_cart[current_cart][i]['quantity']);
    }
    uf_sub_total += parseFloat(pos_cart[current_cart][i]['uf'] * pos_cart[current_cart][i]['quantity']);

    cart_html += '<tr>';
    if (pos_cart[current_cart][i]['product_index'] == 0) {
      cart_html += '  <td class="text-center"><img src="' + no_image + '" height="30" width="30" style="border-radius: 5px;"></td>';
    } else {
      cart_html += '  <td class="text-center"><img src="' + pos_products[pos_cart[current_cart][i]["product_index"]]["image"] + '" height="30" width="30" style="border-radius: 5px;"></td>';
    }
    cart_html += '  <td class="text-center">' + pos_cart[current_cart][i]["name"];

    if (pos_cart[current_cart][i]['product_index'] != 0) {
      cart_html += '    <ul style="list-style: none; padding: 0;">';
      var option_length = Object.keys(pos_cart[current_cart][i]['options']).length;
      if (option_length) {
        var cart_option = '';
        for (var k = 0; k < option_length; k++) {
          if ((cart_option == '') || pos_cart[current_cart][i]['options'][k]['option'] == '') {
            cart_option += pos_cart[current_cart][i]['options'][k]['option'];
          } else {
            cart_option += ', ' + pos_cart[current_cart][i]['options'][k]['option'];
          };
        };
        cart_html += '      <li><span class="label label-warning" style="white-space: normal;">' + cart_option + '</li>';
      };
      cart_html += '    </ul>';
    }

    cart_html += '  </td>';
    cart_html += '  <td class="text-center" title="' + heading_price_update + '" data-toggle="modal" data-target="#updatePrice" onclick="priceUpdate(' + i + ')" style="cursor: help;">';
    if (pos_cart[current_cart][i]['special']) {
      if ((pos_cart[current_cart][i]['uf'] < pos_products[pos_cart[current_cart][i]['product_index']]['price_uf'])) {
        cart_html += '    <span class="line-through">' + pos_cart[current_cart][i]["price"] + '</span> ';
      }
      cart_html += pos_cart[current_cart][i]["special"];
    } else {
      cart_html += pos_cart[current_cart][i]["price"];
    }
    cart_html += '  </td>';
    cart_html += '  <td class="text-center"><i class="fa fa-plus-circle cursor" onclick="cart_product.update(1, ' + pos_cart[current_cart][i]["remove"] + ', ' + pos_cart[current_cart][i]["product_index"] + ')"></i><br>x' + pos_cart[current_cart][i]["quantity"] + '<br><i class="fa fa-minus-circle cursor" onclick="cart_product.update(0, ' + pos_cart[current_cart][i]["remove"] + ', ' + pos_cart[current_cart][i]["product_index"] + ')"></i> </td>';
    cart_html += '  <td class="text-center"><i class="fa fa-times-circle cursor" onclick="cart_product.remove(' + pos_cart[current_cart][i]["remove"] + ')"></i></td>';
    cart_html += '</tr>';
  };

  if (cart_html == '') {
    cart_html = '<tr><td class="text-center">' + text_empty_cart + '</td></tr>';
  }

  cart_tax = parseFloat(cart_tax).toFixed(2);
  uf_sub_total = parseFloat(uf_sub_total).toFixed(2);
  uf_cart_total = parseFloat(cart_tax) + parseFloat(uf_sub_total);
  uf_cart_total = parseFloat(uf_cart_total).toFixed(2);

  f_total_discount = formatPrice(-uf_total_discount);
  f_coupon_disc = formatPrice(-coupon_disc);
  cart_subtotal = formatPrice(uf_sub_total);
  cart_total = formatPrice(uf_cart_total - uf_total_discount - coupon_disc);
  fcart_tax = formatPrice(cart_tax);
  current_total = uf_cart_total;
  current_total_formatted = cart_total;

  if (!(noupdate == 0)) {
    if ($('#input-discname').val()) {
      $('#discname').text($('#input-discname').val());
    }

    $('#item-body').html(cart_html);
    $('#subtotal').html(cart_subtotal);
    $('#cartTotal').html(cart_total);
    $('#discount').text(f_total_discount);
    $('#coupondisc').text(f_coupon_disc);
    if (tax_status != 0) {
      $('#tax').text(fcart_tax);
    }
    $('.cart-total').text(cart_quantity);
  }
}

function cartLocalStorage () {
    if (localStorage) {
        localStorage.pos_cart = JSON.stringify(pos_cart);
        localStorage.pos_holds = JSON.stringify(pos_holds);
        localStorage.current_cart = current_cart;
    };
}

function showAllCarts () {
    var cart_order = '';
    var all_carts = Object.keys(pos_cart);
    for (var n = 0; n < all_carts.length; n++) {
        if (all_carts[n] == current_cart) {
            cart_order += '<a class="abcart cursor" onclick="cart_list.select(' + all_carts[n] + ')" style="border: 1px #444 dotted;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Cart (' + (parseInt(all_carts[n]) + 1) + ')</a>';
        } else {
            cart_order += '<a class="abcart cursor" onclick="cart_list.select(' + all_carts[n] + ')">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Cart (' + (parseInt(all_carts[n]) + 1) + ')</a>';
        };
    };
    $('#current-cart').text(parseInt(current_cart) + 1);
    $('#upper-cart').html(cart_order);
}

function formatPrice(price) {
    if (symbol_position == 'L') {
      formatted = currency_code + parseFloat(price).toFixed(2);
    } else {
      formatted = parseFloat(price).toFixed(2) + currency_code;
    }
    return formatted;
}

function calculateTax(product_id, price) {
  var tax = 0;
  var tax_class_id = pos_products[product_id]['tax_class_id'];
  if (pos_taxes[tax_class_id]) {
    $.each(pos_taxes[tax_class_id], function (key, value) {
      if (value['type'] == 'F') {
        tax += parseFloat(value['rate']);
      } else if (value['type'] == 'P') {
        tax += parseFloat((price * value['rate']) / 100);
      }
    });
  }
  return tax;
}

function removeTax(product_id, price) {
  var tax = 0;
  var tax_class_id = pos_products[product_id]['tax_class_id'];
  if (pos_taxes[tax_class_id]) {
    var percent_tax = 0;
    $.each(pos_taxes[tax_class_id], function (key, value) {
      if (value['type'] == 'P') {
        percent_tax += +value['rate'];
      }
    });
    if (percent_tax) {
      price = (price * 100) / (100 + percent_tax);
    }
  }
  return price;
}
