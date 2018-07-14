var pos_products = {},
pos_taxes,
popular_products,
total_product_count,
no_image,
product_panel,
current_total,
current_total_formatted,
customers,
orders,
all_suppliers,
order_products,
offline = 0,
customer_id = 0,
customer_name = '',
pos_cart = {},
pos_orders = {},
pos_holds = {},
current_cart = 0,
pos_remove_id = 0,
uf_cart_total = 0,
uf_sub_total = 0,
discountApply = 0,
totalDiscount = 0,
uf_total_discount = 0,
coupon = '',
coupon_disc = 0,
cart_tax = 0,
validate_login = true,
currency_update = false,
start = 0;

$(document).ready(function () {
  $( window ).resize(function() {
    $('#pos-side-panel').css('height', $(window).height());
    $('#product-panel').css('height', $(window).height() - 50 - 30);
    $('.sidepanel').css('height', $(window).height() - 50);
    $('#cart-panel').css('height', $(window).height());
    $('#order-container, #other-container').css('height', $(window).height() - 60);
  });
  $('#pos-side-panel').css('height', $(window).height());
  $('#product-panel').css('height', $(window).height() - 50 - 30);
  $('.sidepanel').css('height', $(window).height() - 50);
  $('#cart-panel').css('height', $(window).height());
  $('#order-container, #other-container').css('height', $(window).height() - 60);

  if (user_login == 0) {
    $('#loginModalParent').css('display', 'block');
  } else {
    $('#loginModalParent').css('display', 'none');
    $('#loader').css('display', 'block');
    getPopularProducts();
  };
  product_panel = $('#product-panel');
});

function loginUser () {
  var username = $('#input-username').val();
  var password = $('#input-password').val();
  if (username.length < 4 || password.length < 4) {
    $.toaster({
      priority: 'warning',
      message: message_error_credentials,
      timeout: 5000
    });
    validate_login = false;
  };

  if (validate_login == true) {
    validate_login = false;
    $.ajax({
      url: 'index.php?route=wkpos/wkpos/userLogin',
      dataType: 'json',
      type: 'post',
      data: {username: username, password: password},
      beforeSend: function () {
        $('#loader').css('display', 'block');
      },
      success: function (json) {
        validate_login = true;
        if (json['success']) {
          $.toaster({
            priority: 'success',
            message: json['success'],
            timeout: 5000
          });
          $('.logger-name').text(json['name']);
          $('.logger-post').text('(' + json['group_name'] + ')');
          $('.logger-img').attr('src', json['image']);
          $('#first-name').val(json['firstname']);
          $('#last-name').val(json['lastname']);
          $('#user-name').val(json['username']);
          $('#account-email').val(json['email']);
          user_login = json['user_id'];
          $('#loginModalParent').css('display', 'none');
          if (localStorage.pos_remove_id) {
            $('#clockin').css('display', 'block');
          } else {
            getPopularProducts();
          }
        };
        if (json['error']) {
          $.toaster({
            priority: 'danger',
            message: json['error'],
            timeout: 5000
          });
          $('#loader').css('display', 'none');
        };
      },
      error: function () {
        validate_login = true;
      }
    });
  };
}

function printProducts () {
  product_panel.html('');
  var product_count = total_product_count;

  for (var i = 0; i < product_count; i++) {
    if (pos_products[i]) {
      if (!(show_lowstock_prod == 1) && (pos_products[i]['quantity'] < 1)) {
        continue;
      }
      html = '<div class="col-sm-2 col-xs-6 product-select" product-id="' + pos_products[i]["product_id"] + '" option="' + pos_products[i]["option"] + '">';

      html += '  <img src="' + pos_products[i]["image"] + '" class="product-image" width="100%" height="100%">';
      html += '  <div class="col-xs-12 product-detail">';
      html += '    <b>' + pos_products[i]["name"] + '</b><br />';
      if (pos_products[i]["special"] == 0) {
        html += entry_price + ' <b>' + pos_products[i]["price"] + '</b>';
      } else {
        html += entry_price + ' <b>' + pos_products[i]["special"] + '</b> <span class="line-through">' + pos_products[i]["price"] + '</span>';
      };
      html += '  </div>';
      if (pos_products[i]['option']) {
        html += '<span class="label label-info option-noti" data-toggle="tooltip" title="' + text_option_notifier + '"><i class="fa fa-question-circle"></i></span>';
      }
      if (!(pos_products[i]["special"] == 0)) {
        html += '<span class="label label-danger special-tag" data-toggle="tooltip" title="' + text_special_price + '"><i class="fa fa-star"></i></span>';
      }
      if (parseInt(pos_products[i]['quantity']) <= low_stock) {
        html += '<span class="label label-danger low-stock" data-toggle="tooltip" title="' + text_low_stock + '"><i class="fa fa-exclamation-triangle"></i></span>';
      }
      html += '<span class="label label-info pinfo" data-toggle="tooltip" title="' + button_product_info + '"><i class="fa fa-info-circle"></i></span>';

      html += '</div>';
      product_panel.append(html);
    } else {
      product_count++;
    }
  };
}

function getPopularProducts() {
  $.ajax({
    url: 'index.php?route=wkpos/product/getPopularProducts',
    dataType: 'json',
    type: 'get',
    beforeSend: function () {
      $('#loading-text').text(text_loading_populars);
    },
    success: function (json) {
      $('.progress-bar').css('width', '20%');
      popular_products = json['products'];
      pos_taxes = json['taxes'];
      getAllProducts();
    },
    error: function () {
      $('.progress-bar').addClass('progress-bar-danger').css('width', '20%');
      $('#error-text').append('<br>' + error_load_populars + '<br>');
      if (localStorage && localStorage.pos_taxes) {
        pos_taxes = JSON.parse(localStorage.pos_taxes);
      }
      getAllProducts();
    }
  });
}

function successProducts() {
  if (localStorage) {
    localStorage.pos_products = JSON.stringify(pos_products);
    localStorage.pos_taxes = JSON.stringify(pos_taxes);

    if (localStorage.pos_orders) {
      pos_orders = JSON.parse(localStorage.pos_orders);
    };

    if (localStorage.current_cart) {
      current_cart = localStorage.current_cart;
    } else {
      current_cart = 0;
      localStorage.current_cart = current_cart;
    };

    pos_cart[current_cart] = {};
    if (localStorage.pos_remove_id) {
      pos_remove_id = localStorage.pos_remove_id;
    } else {
      pos_remove_id = 0;
      localStorage.pos_remove_id = pos_remove_id;
    };

    $('#current-cart').text(parseInt(current_cart) + 1);
    if (localStorage.pos_cart) {
      pos_cart = JSON.parse(localStorage.pos_cart);
    };

    if (localStorage.pos_holds) {
      pos_holds = JSON.parse(localStorage.pos_holds);
    };
  };

  $('.cart-hold').text(Object.keys(pos_cart).length - 1);
  if (currency_update == true) {
    updateCartCurrency();
    currency_update = false;
  }
  printProducts();
  printCart();
  getAllCategories();
}

function getAllProducts() {
  $.ajax({
    url: 'index.php?route=wkpos/product&start=' + start,
    dataType: 'json',
    type: 'post',
    data: {user_id: user_login},
    beforeSend: function () {
      $('#loading-text').text(text_loading_products);
    },
    success: function (json) {
      total_product_count = json['total_products'];
      start += json['count'];
      var width = 20 + ((start * 20) / total_product_count);

      $('.progress-bar').css('width', width + '%');
      $.each(json['products'], function (key, value) {
        pos_products[key] = value;
      });

      if (start == total_product_count) {
        no_image = json['no_image'];
        successProducts();
      } else {
        getAllProducts();
      }
    },
    error: function () {
      if (localStorage && localStorage.pos_products) {
        pos_products = JSON.parse(localStorage.pos_products);
        total_product_count = Object.keys(pos_products).length;
        printProducts();
        printCart();
        $('.progress-bar').addClass('progress-bar-danger').css('width', '40%');
        $('#error-text').append('<br>' + error_load_products + '<br>');
      };
      getAllCategories();
    }
  });
}

function getAllCategories() {
  $.ajax({
    url: 'index.php?route=wkpos/category',
    dataType: 'json',
    type: 'post',
    data: {user_id: user_login},
    beforeSend: function () {
      $('#loading-text').text(text_loading_categories);
    },
    success: function (json) {
      $('.progress-bar').css('width', '60%');
      categories = json['categories'];
      var category_html = '';

      for (var i = 0; i < categories.length; i++) {
        category_html += '<div class="margin-10">';
        category_html += '    <label class="categoryProduct cursor" category-id="' + categories[i]['category_id'] + '">' + categories[i]['name'] + '</label>';
          child_length = categories[i]['children'].length;
          if (child_length) {
        category_html += '    <button class="btn btn-default btn-xs eCategory" onclick="return false;"><i class="fa fa-plus"></i></button>';
        category_html += '    <div class="form-group sub-cat">';
              for (var j = 0; j < child_length; j++) {
        category_html += '        <div>';
        category_html += '          <label class="categoryProduct cursor" category-id="' + categories[i]['children'][j]['category_id'] + '">' + categories[i]['children'][j]['name'] + '</label>';
        category_html += '        </div>';
              }
        category_html += '    </div>';
          }
        category_html += '</div>';
      }
      $('#categoryList .modal-body').prepend(category_html);
      getAllCustomers();
    },
    error: function () {
      $('.progress-bar').addClass('progress-bar-danger').css('width', '60%');
      $('#error-text').append(error_load_categories + '<br>');
      $('.fa-spin').removeClass('fa-spin');
      getAllCustomers();
    }
  });
}

function getAllCustomers(nocontinue) {
  $.ajax({
    url: 'index.php?route=wkpos/customer',
    dataType: 'json',
    type: 'get',
    beforeSend: function () {
      $('#loading-text').text(text_loading_customers);
    },
    success: function (json) {
      $('.progress-bar').css('width', '80%');
      customers = json['customers'];
      if (!nocontinue) {
        getAllOrders();
      }
    },
    error: function () {
      $('.progress-bar').addClass('progress-bar-danger').css('width', '80%');
      $('#error-text').append(error_load_customers + '<br>');
      if (localStorage.pos_orders) {
        pos_orders = JSON.parse(localStorage.pos_orders);
      };
      getAllOrders();
    }
  });
}

function getAllOrders() {
  $.ajax({
    url: 'index.php?route=wkpos/order',
    dataType: 'json',
    type: 'post',
    data: {user_id: user_login},
    beforeSend: function () {
      $('#loading-text').text(text_loading_orders);
    },
    success: function (json) {
      $('.progress-bar').css('width', '100%');
      orders = json['orders'];
      order_products = json['order_products'];
      getRequestHistory();
      $('#postorder').find('.buttons-sp:first').removeAttr('disabled');
      $('.wkorder:first').trigger('click');
      setTimeout(function () {
        $('#loader').css('display', 'none');
        $('.progress, #loading-text').addClass('hide');
      }, 700);
    },
    error: function () {
      $('.progress-bar').addClass('progress-bar-danger').css('width', '100%');
      $('#error-text').append(error_load_orders + '<br>');
      setTimeout(function () {
        $('#loader').css('display', 'none');
        $('.progress, #loading-text, #error-text').addClass('hide');
      }, 5000);
    }
  });
}

function addToCart (thisthis, options) {
  var by_barcode = false;
  if (thisthis) {
    var product_id = $(thisthis).attr('product-id');
    var option = $(thisthis).attr('option');
  } else {
    var product_id = options.product_id;
    var option = options.option;
    var thisthis = options.thisthis;
    by_barcode = true;
  }

  if (option == 'false') {
    cart_product.add(product_id, 1, false, thisthis);
  } else {
    var option_length = Object.keys(pos_products[product_id]['options']).length;
    var product_options = pos_products[product_id]['options'];
    var option_html = '';

    for (var i = 0; i < option_length; i++) {
      if (product_options[i]['required'] == 1) {
        var is_required = ' required';
      } else {
        var is_required = '';
      };
      var option_value_length = Object.keys(product_options[i]['product_option_value']).length;

      if (product_options[i]['type'] == 'select') {
        option_html += '<div class="form-group' + is_required + '">';
        option_html += '  <label class="control-label" for="input-option' + product_options[i]["product_option_id"] + '">' + product_options[i]["name"] + '</label>';
        option_html += '  <select name="option[' + product_options[i]["product_option_id"] + ']" id="input-option' + product_options[i]["product_option_id"] + '" class="form-control">';
        option_html += '    <option value="">' + text_select + '</option>';

        for (var j = 0; j < option_value_length; j++) {
          option_html += '<option value="' + product_options[i]["product_option_value"][j]["product_option_value_id"] + '">' + product_options[i]["product_option_value"][j]["name"];
          if (product_options[i]["product_option_value"][j]['price']) {
            option_html += ' (' + product_options[i]["product_option_value"][j]['price_prefix'] + product_options[i]["product_option_value"][j]['price'] + ')';
          }
          option_html += '</option>';
        };

        option_html += '  </select>';
        option_html += '</div>';
      }
      if (product_options[i]['type'] == 'radio') {
        option_html += '<div class="form-group' + is_required + '">';
        option_html += '  <label class="control-label">' + product_options[i]["name"] + '</label>';
        option_html += '  <div id="input-option' + product_options[i]["product_option_id"] + '">';

        for (var j = 0; j < option_value_length; j++) {
          option_html += '<div class="radio">';
          option_html += '  <label>';
          option_html += '    <input type="radio" name="option[' + product_options[i]["product_option_id"] + ']" value="' + product_options[i]["product_option_value"][j]["product_option_value_id"] + '" />';
          option_html += '    ' + product_options[i]["product_option_value"][j]["name"] + '';
          if (product_options[i]["product_option_value"][j]['price']) {
            option_html += ' (' + product_options[i]["product_option_value"][j]['price_prefix'] + product_options[i]["product_option_value"][j]['price'] + ')';
          }
          option_html += '  </label>';
          option_html += '</div>';
        }
        option_html += '  </div>';
        option_html += '</div>';
      }
      if (product_options[i]['type'] == 'checkbox') {
        option_html += '<div class="form-group' + is_required + '">';
        option_html += '  <label class="control-label">' + product_options[i]["name"] + '</label>';
        option_html += '  <div id="input-option' + product_options[i]["product_option_id"] + '">';

        for (var j = 0; j < option_value_length; j++) {
          option_html += '<div class="checkbox">';
          option_html += '  <label>';
          option_html += '    <input type="checkbox" name="option[' + product_options[i]["product_option_id"] + '][]" value="' + product_options[i]["product_option_value"][j]["product_option_value_id"] + '" />';

          if (product_options[i]["product_option_value"][j]["image"]) {
            if (product_options[i]["product_option_value"][j]["price"]) {
              var image_alt = product_options[i]["product_option_value"][j]["name"] + ' (' + product_options[i]["product_option_value"][j]["price_prefix"] + product_options[i]["product_option_value"][j]["price"] + ')';
            } else {
              var image_alt = product_options[i]["product_option_value"][j]["name"];
            };
            option_html += '<img src="' + product_options[i]["product_option_value"][j]["image"] + '" alt="' + image_alt + '" class="img-thumbnail" />';
          }
          option_html += product_options[i]["product_option_value"][j]["name"];
          if (product_options[i]["product_option_value"][j]['price']) {
            option_html += ' (' + product_options[i]["product_option_value"][j]['price_prefix'] + product_options[i]["product_option_value"][j]['price'] + ')';
          }
          option_html += '  </label>';
          option_html += '</div>';
          }
        option_html += '  </div>';
        option_html += '</div>';
      }
      if (product_options[i]['type'] == 'image') {
        option_html += '<div class="form-group' + is_required + '">';
        option_html += '  <label class="control-label">' + product_options[i]["name"] + '</label>';
        option_html += '  <div id="input-option' + product_options[i]["product_option_id"] + '">';

        for (var j = 0; j < option_value_length; j++) {
          if (product_options[i]["product_option_value"][j]["price"]) {
            var image_alt = product_options[i]["product_option_value"][j]["name"] + ' (' + product_options[i]["product_option_value"][j]["price_prefix"] + product_options[i]["product_option_value"][j]["price"] + ')';
          } else {
            var image_alt = product_options[i]["product_option_value"][j]["name"];
          };

          option_html += '<div class="radio">';
          option_html += '  <label>';
          option_html += '    <input type="radio" name="option[' + product_options[i]["product_option_id"] + ']" value="' + product_options[i]["product_option_value"][j]["product_option_value_id"] + '" />';
          if (product_options[i]["product_option_value"][j]["image"] == null) {
            option_html += '    <img src="' + no_image + '" alt="' + image_alt + '" class="img-thumbnail" /> ' + product_options[i]["product_option_value"][j]["name"];
          } else {
            option_html += '    <img src="' + product_options[i]["product_option_value"][j]["image"] + '" alt="' + image_alt + '" class="img-thumbnail" /> ' + product_options[i]["product_option_value"][j]["name"];
          };
          if (product_options[i]["product_option_value"][j]['price']) {
            option_html += ' (' + product_options[i]["product_option_value"][j]['price_prefix'] + product_options[i]["product_option_value"][j]['price'] + ')';
          }
          option_html += '  </label>';
          option_html += '</div>';
          }
        option_html += '  </div>';
        option_html += '</div>';
      }
      if (product_options[i]['type'] == 'text') {
        option_html += '<div class="form-group' + is_required + '">';
        option_html += '  <label class="control-label" for="input-option' + product_options[i]["product_option_id"] + '">' + product_options[i]["name"] + '</label>';
        option_html += '<input type="text" name="option[' + product_options[i]["product_option_id"] + ']" value="' + product_options[i]["value"] + '" placeholder="' + product_options[i]["name"] + '" id="input-option' + product_options[i]["product_option_id"] + '" class="form-control" />';
        option_html += '</div>';
      }
      if (product_options[i]['type'] == 'textarea') {
        option_html += '<div class="form-group' + is_required + '">';
        option_html += '  <label class="control-label" for="input-option' + product_options[i]["product_option_id"] + '">' + product_options[i]["name"] + '</label>';
        option_html += '<textarea name="option[' + product_options[i]["product_option_id"] + ']" rows="5" placeholder="' + product_options[i]["name"] + '" id="input-option' + product_options[i]["product_option_id"] + '" class="form-control">' + product_options[i]["value"] + '</textarea>';
      option_html += '</div>';
      }
      if (product_options[i]['type'] == 'file') {
        option_html += '<div class="form-group' + is_required + '">';
        option_html += '  <label class="control-label">' + product_options[i]["name"] + '</label>';
        option_html += '<button type="button" id="button-upload' + product_options[i]["product_option_id"] + '" data-loading-text="' + text_loading + '" class="btn btn-default btn-block"><i class="fa fa-upload"></i> ' + button_upload + '</button>';
        option_html += '<input type="hidden" name="option[' + product_options[i]["product_option_id"] + ']" value="" id="input-option' + product_options[i]["product_option_id"] + '" />';
        option_html += '</div>';
      }
      if (product_options[i]['type'] == 'date') {
        option_html += '<div class="form-group' + is_required + '">';
        option_html += '  <label class="control-label" for="input-option' + product_options[i]["product_option_id"] + '">' + product_options[i]["name"] + '</label>';
        option_html += '<div class="input-group date">';
        option_html += '  <input type="text" name="option[' + product_options[i]["product_option_id"] + ']" value="' + product_options[i]["value"] + '" data-date-format="YYYY-MM-DD" id="input-option' + product_options[i]["product_option_id"] + '" class="form-control" />';
        option_html += '  <span class="input-group-btn">';
        option_html += '  <button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>';
        option_html += '  </span></div>';
        option_html += '</div>';
      }
      if (product_options[i]['type'] == 'datetime') {
        option_html += '<div class="form-group' + is_required + '">';
        option_html += '  <label class="control-label" for="input-option' + product_options[i]["product_option_id"] + '">' + product_options[i]["name"] + '</label>';
        option_html += '<div class="input-group datetime">';
        option_html += '  <input type="text" name="option[' + product_options[i]["product_option_id"] + ']" value="' + product_options[i]["value"] + '" data-date-format="YYYY-MM-DD HH:mm" id="input-option' + product_options[i]["product_option_id"] + '" class="form-control" />';
        option_html += '  <span class="input-group-btn">';
        option_html += '  <button type="button" class="btn btn-default"><i class="fa fa-calendar"></i></button>';
        option_html += '  </span></div>';
        option_html += '</div>';
      }
      if (product_options[i]['type'] == 'time') {
        option_html += '<div class="form-group' + is_required + '">';
        option_html += '  <label class="control-label" for="input-option' + product_options[i]["product_option_id"] + '">' + product_options[i]["name"] + '</label>';
        option_html += '<div class="input-group time">';
        option_html += '  <input type="text" name="option[' + product_options[i]["product_option_id"] + ']" value="' + product_options[i]["value"] + '" data-date-format="HH:mm" id="input-option' + product_options[i]["product_option_id"] + '" class="form-control" />';
        option_html += '  <span class="input-group-btn">';
        option_html += '  <button type="button" class="btn btn-default"><i class="fa fa-calendar"></i></button>';
        option_html += '  </span></div>';
        option_html += '</div>';
      }
    };
    option_html += '<input type="hidden" name="product_id" value="' + product_id + '">';
    if (by_barcode) {
      option_html += '<button type="button" onclick="cart_product.add('  + product_id + ', 1, true, this, true);" class="form-control btn-primary"><span class="hidden-xs hidden-sm hidden-md">' + button_cart + '</span> <i class="fa fa-shopping-cart"></i></button>';
    } else {
      option_html += '<button type="button" onclick="cart_product.add('  + product_id + ', 1, true, this);" class="form-control btn-primary"><span class="hidden-xs hidden-sm hidden-md">' + button_cart + '</span> <i class="fa fa-shopping-cart"></i></button>';
    }
    $('#global-modal-title').text(text_product_options);
    $('#posProductOptions').html(option_html);
    $('#buttonModal').trigger('click');
    datetimepickerFunction();
  };
};

$(document).on('keyup', '#search', function () {
  var keyword = $('#search').val();
  searchProduct(keyword)
})

function searchProduct (keyword) {
  var to_search = keyword.replace('\\', "");
  var to_search = to_search.replace('[', "");
  var search_check = true;
  $('#search').val(to_search);
  if (keyword !== to_search) {
    $.toaster({
      priority: 'warning',
      message: error_keyword,
      timeout: 3000
    });
    search_check = false;
  };

  if (search_check) {
    var search_keyword = to_search.toLowerCase();
    if (!(keyword == '')) {
      $('#product-panel').prev().text(text_search + ' - ' + keyword + ' ( ' + text_all_products + ' )');
    } else {
      $('#product-panel').prev().text(text_all_products);
    }

    $('#loader').css('display', 'block');
    product_panel.html('');
    var product_count = total_product_count;
    for (var i = 0; i < product_count; i++) {
      if (pos_products[i]) {
        if (!(show_lowstock_prod == 1) && (pos_products[i]['quantity'] < 1)) {
          continue;
        }
      if (pos_products[i]["name"].toLowerCase().search(search_keyword) != '-1' || pos_products[i]["model"].toLowerCase().search(search_keyword) != '-1' || pos_products[i]["sku"].toLowerCase().search(search_keyword) != '-1') {
          html = '<div class="col-sm-2 col-xs-6 product-select" product-id="' + pos_products[i]["product_id"] + '" option="' + pos_products[i]["option"] + '">';

          html += '  <img src="' + pos_products[i]["image"] + '" class="product-image" width="100%" height="100%">';
          html += '  <div class="col-xs-12 product-detail">';
          html += '    <b>' + pos_products[i]["name"] + '</b><br />';
          if (pos_products[i]["special"] == 0) {
            html += entry_price + ' <b>' + pos_products[i]["price"] + '</b>';
          } else {
            html += entry_price + ' <b>' + pos_products[i]["special"] + '</b> <span class="line-through">' + pos_products[i]["price"] + '</span>';
          };
          html += '  </div>';
          if (pos_products[i]['option']) {
            html += '<span class="label label-info option-noti" data-toggle="tooltip" title="' + text_option_notifier + '"><i class="fa fa-question-circle"></i></span>';
          }
          if (!(pos_products[i]["special"] == 0)) {
            html += '<span class="label label-danger special-tag" data-toggle="tooltip" title="' + text_special_price + '"><i class="fa fa-star"></i></span>';
          }
          if (parseInt(pos_products[i]['quantity']) <= low_stock) {
            html += '<span class="label label-danger low-stock" data-toggle="tooltip" title="' + text_low_stock + '"><i class="fa fa-exclamation-triangle"></i></span>';
          }
          html += '<span class="label label-info pinfo" data-toggle="tooltip" title="' + button_product_info + '"><i class="fa fa-info-circle"></i></span>';

          html += '</div>';
          product_panel.append(html);
        }
      } else {
        product_count++
      };
    };
    if (product_panel.text() == '') {
      product_panel.html('<div class="no-product"><strong>' + error_products + '</strong></div>');
    };
    $('#loader').css('display', 'none');
  };
}

function onlineStatus ($toast) {
  var status_button = $('#mode');

  if(status_button.hasClass('label-danger')) {
    if (navigator.onLine) {
      if ($toast) {
        $.toaster({
          priority: 'success',
          message: text_online_mode,
          timeout: 2000
        });
      };
      status_button.removeClass('label-danger').addClass('label-success').html('<i class="fa fa-toggle-on"></i> <span class="hidden-xs">' + text_online + '</span>');
      offline = 0;
    } else {
      if ($toast) {
        $.toaster({
          priority: 'danger',
          message: error_enter_online,
          timeout: 3000
        });
      };
    }
  } else {
    if ($toast) {
      $.toaster({
        priority: 'warning',
        message: text_offline_mode,
        timeout: 2000
      });
    };
    status_button.removeClass('label-success').addClass('label-danger').html('<i class="fa fa-toggle-off"></i> <span class="hidden-xs">' + text_offline + '</span>');
    offline = 1;
  }
}

$(document).on('click', '#mode', function () {
  onlineStatus(1);
});

window.addEventListener('online',  onlineStatus);
window.addEventListener('offline', onlineStatus);

function datetimepickerFunction() {
  $('.date').datetimepicker({
    pickTime: false
  });

  $('.datetime').datetimepicker({
    pickDate: true,
    pickTime: true
  });

  $('.time').datetimepicker({
    pickDate: false
  });
}

$(document).on('click', '#more-carts', function () {
  $('#upper-cart').slideToggle();
});

$(document).on('click', '.categoryProduct', function () {
  var category_id = $(this).attr('category-id');
  if (category_id == undefined) {
    return;
  };

  $('.categoryProduct').removeClass('onfocus');
  $(this).addClass('onfocus');
  $('#loader').css('display', 'block');
  product_panel.html('');
  product_panel.prev().text($(this).text());
  var product_count = total_product_count;

  if (category_id == 0) {
    for (var i = 0; i < product_count; i++) {
      if (pos_products[i]) {
        if (!(show_lowstock_prod == 1) && (pos_products[i]['quantity'] < 1)) {
          continue;
        }
        if ($.inArray( pos_products[i]["product_id"], popular_products ) != '-1') {
          html = '<div class="col-sm-2 col-xs-6 product-select" product-id="' + pos_products[i]["product_id"] + '" option="' + pos_products[i]["option"] + '">';

          html += '  <img src="' + pos_products[i]["image"] + '" class="product-image" width="100%" height="100%">';
          html += '  <div class="col-xs-12 product-detail">';
          html += '    <b>' + pos_products[i]["name"] + '</b><br />';
          if (pos_products[i]["special"] == 0) {
            html += entry_price + ' <b>' + pos_products[i]["price"] + '</b>';
          } else {
            html += entry_price + ' <b>' + pos_products[i]["special"] + '</b> <span class="line-through">' + pos_products[i]["price"] + '</span>';
          };
          html += '  </div>';
          if (pos_products[i]['option']) {
            html += '<span class="label label-info option-noti" data-toggle="tooltip" title="' + text_option_notifier + '"><i class="fa fa-question-circle"></i></span>';
          }
          if (!(pos_products[i]["special"] == 0)) {
            html += '<span class="label label-danger special-tag" data-toggle="tooltip" title="' + text_special_price + '"><i class="fa fa-star"></i></span>';
          }
          if (parseInt(pos_products[i]['quantity']) <= low_stock) {
            html += '<span class="label label-danger low-stock" data-toggle="tooltip" title="' + text_low_stock + '"><i class="fa fa-exclamation-triangle"></i></span>';
          }
          html += '<span class="label label-info pinfo" data-toggle="tooltip" title="' + button_product_info + '"><i class="fa fa-info-circle"></i></span>';

          html += '</div>';
          product_panel.append(html);
        }
      } else {
        product_count++;
      };

    };
  } else {
    for (var i = 0; i < product_count; i++) {
      if (pos_products[i]) {
        if (!(show_lowstock_prod == 1) && (pos_products[i]['quantity'] < 1)) {
          continue;
        }
        if ($.inArray( category_id, pos_products[i]["categories"] ) != '-1') {
          html = '<div class="col-sm-2 col-xs-6 product-select" product-id="' + pos_products[i]["product_id"] + '" option="' + pos_products[i]["option"] + '">';

          html += '  <img src="' + pos_products[i]["image"] + '" class="product-image" width="100%" height="100%">';
          html += '  <div class="col-xs-12 product-detail">';
          html += '    <b>' + pos_products[i]["name"] + '</b><br />';
          if (pos_products[i]["special"] == 0) {
            html += entry_price + ' <b>' + pos_products[i]["price"] + '</b>';
          } else {
            html += entry_price + ' <b>' + pos_products[i]["special"] + '</b> <span class="line-through">' + pos_products[i]["price"] + '</span>';
          };
          html += '  </div>';
          if (pos_products[i]['option']) {
            html += '<span class="label label-info option-noti" data-toggle="tooltip" title="' + text_option_notifier + '"><i class="fa fa-question-circle"></i></span>';
          }
          if (!(pos_products[i]["special"] == 0)) {
            html += '<span class="label label-danger special-tag" data-toggle="tooltip" title="' + text_special_price + '"><i class="fa fa-star"></i></span>';
          }
          if (parseInt(pos_products[i]['quantity']) <= low_stock) {
            html += '<span class="label label-danger low-stock" data-toggle="tooltip" title="' + text_low_stock + '"><i class="fa fa-exclamation-triangle"></i></span>';
          }
          html += '<span class="label label-info pinfo" data-toggle="tooltip" title="' + button_product_info + '"><i class="fa fa-info-circle"></i></span>';

          html += '</div>';
          product_panel.append(html);
        }
      } else {
        product_count++;
      };
    };
  }
  if (product_panel.text() == '') {
    product_panel.html('<div class="no-product"><strong>' + error_no_category_product + '</strong></div>');
  };
  $('.in').trigger('click');
  $('#loader').css('display', 'none');
});

$(document).on('click', '.eCategory', function () {
  if ($(this).children().hasClass('fa-plus')) {
    $(this).children().removeClass('fa-plus');
    $(this).children().addClass('fa-minus');
    $(this).next().slideDown();
  } else {
    $(this).children().removeClass('fa-minus');
    $(this).children().addClass('fa-plus');
    $(this).next().slideUp();
  };
});

$(document).on('keyup', '#searchCustomer', function () {
  var keyword = $('#searchCustomer').val();
  if (keyword == '') {
    return;
  };
  keyword = keyword.toLowerCase();

  var keys = Object.keys(customers);
  var customer_html = '';
  customer_html += '<hr class="margin-hr">';
  for (var i = 0; i < customers.length; i++) {
    if ((customers[i]["name"].toLowerCase().search(keyword) != '-1') || (customers[i]["email"].toLowerCase().search(keyword) != '-1') || (customers[i]["telephone"].toLowerCase().search(keyword) != '-1')) {
      customer_html += '<div customer-index="' + i + '" class="cursor selectCustomer">' + customers[i]["name"] + ' (' + customers[i]["telephone"] + ' <i class="fa fa-phone"></i>) (' + customers[i]["email"] + ' <i class="fa fa-envelope"></i>)</div>';
    }
  };
  if (customer_html == '') {
    customer_html += '<hr class="margin-hr">';
    customer_html += '<span>' + error_no_customer + '</span>';
  };
  $('#putCustomer').html(customer_html);
});

$(document).on('click', '.selectCustomer', function () {
  $('#removeCoupon').trigger('click');
  customer_index = $(this).attr('customer-index');
  customer_id = customers[customer_index]['customer_id'];
  customer_name = customers[customer_index]['name'];
  $('#customer-name').text(customer_name);
  $('.in').trigger('click');
  $.toaster({
    priority: 'success',
    message: text_select_customer,
    timeout: 3000
  });
});

$(document).on('click', '#addCustomer', function () {
  if (offline) {
    $.toaster({
      priority: 'danger',
      message: error_customer_add,
      timeout: 3000
    });
  } else {
    $(this).addClass('hide');
    $('.searchCustomer').addClass('hide');
    $('#customerSearch .modal-dialog').removeClass('modal-sm').addClass('modal-md');
    $('.addCustomer').removeClass('hide');
  };
});

$(document).on('click', '#button-customer', function () {
  $('#addCustomer').removeClass('hide');
  $('.searchCustomer').removeClass('hide');
  $('#customerSearch .modal-dialog').removeClass('modal-md').addClass('modal-sm');
  $('.addCustomer').addClass('hide');
});

$(document).on('click', '#removeCustomer', function () {
  $('.in').trigger('click');
  customer_id = 0;
  $('#customer-name').text(text_customer_select);
  $.toaster({
    priority: 'success',
    message: text_remove_customer,
    timeout: 3000
  });
});

$(document).on('click', '.pinfo', function() {
  var product_id = $(this).parent().attr('product-id');
  $('#detail-image').attr('src', pos_products[product_id]['image']);
  $('#productName').text(pos_products[product_id]['name']);
  $('#productPrice').text(pos_products[product_id]['price']);
  $('#productItem').text(pos_products[product_id]['quantity']);
  $('#supplier-info').css('display', 'none');
  $('#buttonProductDetails').trigger('click');
  to_cart = 'true';
});

$(document).on('click', '#others .product-select', function() {
  var product_id = $(this).attr('product-id');
  $('#detail-image').attr('src', pos_products[product_id]['image']);
  $('#productName').text(pos_products[product_id]['name']);
  $('#productPrice').text(pos_products[product_id]['price']);
  $('#productItem').text(pos_products[product_id]['quantity']);
  $('#supplier-info').css('display', '');
  var html = '';
  var suppliers = pos_products[product_id]['suppliers'];
  var suppliers_length = Object.keys(suppliers).length;
  if (suppliers_length) {
    var k = 0;
    for (var i = 0; i < suppliers_length; i++) {
      if (suppliers[i]) {
        html += (++k) + ': ' + suppliers[i]['name'] + '<br/>';
      } else {
        suppliers_length++;
      }
    }
  }
  if (html == '') {
    html = '<span class="text-danger">' + error_no_supplier + '</span>';
  }
  $('#productSuppliers').html(html);

  $('#buttonProductDetails').trigger('click');
});

$(document).on('mouseup', '#product-panel .product-select', function(e) {
  if ($(e.target).hasClass('fa-info-circle') || $(e.target).hasClass('pinfo')) {
    return;
  }
  addToCart(this);
});

function showAllProducts () {
  product_panel.html('');
  product_panel.prev().text(text_all_products);
  $('.categoryProduct').removeClass('onfocus');
  printProducts();
  $('.in').trigger('click');
}

$(document).on('click', '.button-payment', function () {
  if ($('.payment-parent').hasClass('panel-show')) {
    $('#button-payment').removeClass('onfocus');
    $('.payment-parent').removeClass('panel-show');
    return;
  };

  if (JSON.stringify(pos_cart[current_cart]) == '{}') {
    $.toaster({
      priority: 'warning',
      message: error_checkout,
      timeout: 3000
    });
    return;
  } else {
    $('.sidepanel').removeClass('sidepanel-show');
  };

  $('.parents').removeClass('panel-show');
  $('.wksidepanel').removeClass('onfocus');
  $('#button-payment').addClass('onfocus');
  $('.payment-parent').addClass('panel-show');
});

$(document).on('click', '.wkpaymentmethod', function () {
  $('.fa-chevron-right').addClass('hide');
  $('.payment-child2>div').addClass('hide');
  $('.all-payment').removeClass('hide');
  $('#orderNote').val('');
  $(this).children('.fa-chevron-right').removeClass('hide');
  $('.text-danger').remove();
  $('.has-error').removeClass('has-error');
  var type = $(this).attr('type');
  if (type == 'cash-payment') {
    $('.cash-payment').removeClass('hide');
    $('#balance-due').text(current_total_formatted);
    $('.accept-payment').attr('ptype', 'cash');

    if (symbol_position == 'L') {
      var change = currency_code + '0.00';
    } else {
      var change = '0.00' + currency_code;
    }
    $('#change').text(change);
    $('#amount-tendered').val('');
  } else if (type == 'card-payment') {
    $('.accept-payment').attr('ptype', 'card');
    // $('.card-payment').removeClass('hide');
  };
});

$(document).on('click', '.wkaccounts', function () {
  $('.wkaccounts').removeClass('onselect');
  $(this).addClass('onselect');
  $('.fa-chevron-right').addClass('hide');
  $(this).children('.fa-chevron-right').removeClass('hide');
  var type = $(this).attr('type');
  if (type == 'basic') {
    $('.other-account').addClass('hide');
    $('.basic-account').removeClass('hide');
  };
  if (type == 'other') {
    $('.basic-account').addClass('hide');
    $('.other-account').removeClass('hide');
  };
});

//Function to allow only numbers to textbox
function validate(key, thisthis, nodot) {
  //getting key code of pressed key
  var keycode = (key.which) ? key.which : key.keyCode;

  if (keycode == 46) {
    if (nodot) {
      return false;
    }

    var val = $(thisthis).val();
    if (val == val.replace('.', '')) {
      return true;
    } else {
      return false;
    }
  }

  //comparing pressed keycodes
  if (!(keycode == 8 || keycode == 9 || keycode == 46 || keycode == 116) && (keycode < 48 || keycode > 57)) {
    return false;
  } else {
    return true;
  }
}

$(document).on('keyup', '#amount-tendered', function () {
  var cash = $(this).val();

  if (!isNaN(cash)) {
    var change = cash - (current_total - uf_total_discount - coupon_disc);
    var change_formatted;

    $('.text-danger').remove();

    if (symbol_position == 'L') {
      change_formatted = currency_code + parseFloat(change).toFixed(2);
      var reset_change = currency_code + '0.00';
      var balance_due = currency_code + Math.abs(parseFloat(change).toFixed(2));
    } else {
      change_formatted = parseFloat(change).toFixed(2) + currency_code;
      var reset_change = '0.00' + currency_code;
      var balance_due = Math.abs(parseFloat(change).toFixed(2)) + currency_code;
    }
    $('#change').text(reset_change);

    if (change < 0) {
      $('#balance-due').after('<span class="text-danger">'+ text_balance_due + ' ' + balance_due + '</span>');
    } else {
      $('#change').text(change_formatted);
    };
  }
});

$(document).on('click', '.accept-payment', function () {
  if (JSON.stringify(pos_cart[current_cart]) == '{}') {
    $.toaster({
      priority: 'warning',
      message: error_checkout,
      timeout: 3000
    });
    return;
  };
  var thisthis = $(this);

  if ($(this).attr('ptype') == 'cash') {
    var amount_box = $('body #amount-tendered');
    var amount_tendered = amount_box.val();
    var total = parseFloat(current_total - uf_total_discount - coupon_disc);
    var tendered = parseFloat(amount_tendered ? amount_tendered : 0);
// check here
    if (tendered < total) {
      amount_box.parent().parent().parent().addClass('has-error');
      var accept = confirm(text_tendered_confirm);
      if (accept == true) {
        acceptPayment(thisthis);
      }
    } else {
      acceptPayment(thisthis);
    }
  } else if ($(this).attr('ptype') == 'card') {
    var accept = confirm(text_card_confirm);
    if (accept == true) {
      acceptPayment(thisthis);
    }
  }
});

function acceptPayment(thisthis) {
  $('#loader').css('display', 'block');
  var payment_type = thisthis.attr('ptype');
  var order_note = $('#orderNote').val();
  var discount = {};
  discount['discount'] = uf_total_discount;
  discount['name'] = $('#input-discname').val();
  var pos_coupon = {};
  pos_coupon['coupon'] = coupon;
  pos_coupon['discount'] = coupon_disc;
  var tax = parseFloat(cart_tax).toFixed(2);
  $('body #amount-tendered').parent().parent().parent().removeClass('has-error');

  if (!offline) {
    $.ajax({
      url: 'index.php?route=wkpos/order/addOrder',
      type: 'post',
      dataType: 'json',
      data: {cart: pos_cart[current_cart], payment_method: payment_type, customer_id: customer_id, user_id: user_login, order_note: order_note, discount: discount, coupon: pos_coupon, tax: tax, currency: currency},
      beforeSend: function () {
        $('#loader').css('display', 'block');
      },
      success: function (json) {
        if (json['success']) {
          $.toaster({
            priority: 'success',
            message: json['success'],
            timeout: 5000
          });
        };
        getAllOrders();
        $('#postorder').css('display', 'block').find('.buttons-sp:first').attr('disabled', 'disabled');
      },
      complete: function () {
        $('#loader').css('display', 'none');
      }
    });
  } else {
    var order = {};
    var d = new Date();

    order['cart'] = pos_cart[current_cart];
    order['payment'] = payment_type;
    order['customer'] = customer_id;
    if (!(customer_name == '')) {
      order['cname'] = customer_name;
    }
    order['date'] = d.getFullYear() + '-' + d.getMonth() + '-' + d.getDate();
    order['time'] = d.getHours() + ':' + d.getMinutes() + ':' + d.getSeconds();
    order['user_login'] = user_login;
    order['order_note'] = order_note;
    order['cashier'] = cashier;
    order['discount'] = discount;
    order['tax'] = tax;
    order['currency'] = currency;
    order['coupon'] = pos_coupon;
    order['txn_id'] = Math.floor((Math.random() * 999999999) + 100000000);
    var olength = Object.keys(pos_orders).length;
    pos_orders[olength] = order;
    $.toaster({
      priority: 'success',
      message: text_order_success,
      timeout: 5000
    });
    if (localStorage) {
      localStorage.pos_orders = JSON.stringify(pos_orders);
    };
    $('#postorder').css('display', 'block');
    $('#loader').css('display', 'none');
  };
  uf_total_discount = 0;
  discountApply = 0;
  coupon_disc = 0;
  coupon = '';
  $('.payment-parent').removeClass('panel-show');
  $('#button-payment').removeClass('onfocus');
  $('.all-payment').addClass('hide');
  $('.fa-chevron-right').addClass('hide');
  for (var i = 0; i < Object.keys(pos_cart[current_cart]).length; i++) {
    if (pos_products[pos_cart[current_cart][i]['product_id']]) {
      pos_products[pos_cart[current_cart][i]['product_id']]['quantity'] -= pos_cart[current_cart][i]['quantity'];
    }
  }
  cart_list.delete(0);
  customer_id = 0;
  customer_name = '';
  $('#customer-name').text(text_customer_select);
  $('#discrow,#couprow').css('display', 'none');
  $('.cash-payment').addClass('hide');
}

$(document).on('click', '#button-order', function () {
  // hiding the cash payment panel
  $('.cash-payment').addClass('hide');
  if ($('.order-parent').hasClass('panel-show')) {
    $(this).removeClass('onfocus');
    $('.order-parent').removeClass('panel-show');
    $('.sidepanel').removeClass('sidepanel-show');
    return;
  };
  $('#sidepanel-inner').css('display', 'none');
  $('.parents').removeClass('panel-show');
  $('.wksidepanel').removeClass('onfocus');
  $(this).addClass('onfocus');
  $('.order-parent').addClass('panel-show');
  $('.sidepanel').addClass('sidepanel-show');
  if ($('#orders').text() == '') {
    $('.order-child .wkorder:first').trigger('click');
  }
});

$(document).on('click', '#button-account', function () {
  // hiding the cash payment panel
  $('.cash-payment').addClass('hide');
  $('.sidepanel').removeClass('sidepanel-show');
  if ($('.account-parent').hasClass('panel-show')) {
    $(this).removeClass('onfocus');
    $('.account-parent').removeClass('panel-show');
    return;
  };
  $('.parents').removeClass('panel-show');
  $('.wksidepanel').removeClass('onfocus');
  $(this).addClass('onfocus');
  $('.account-parent').addClass('panel-show');
  $('.wkaccounts:first').next().trigger('click');
});

$(document).on('click', '#button-other', function () {
  // hiding the cash payment panel
  $('.cash-payment').addClass('hide');
  if ($('.other-parent').hasClass('panel-show')) {
    $(this).removeClass('onfocus');
    $('.other-parent').removeClass('panel-show');
    $('.sidepanel').removeClass('sidepanel-show');
    return;
  };
  $('#sidepanel-inner').css('display', 'none');
  $('.parents').removeClass('panel-show');
  $('.wksidepanel').removeClass('onfocus');
  $(this).addClass('onfocus');
  $('.other-parent').addClass('panel-show');
  $('.sidepanel').removeClass('sidepanel-show');
  if ($('#others').text() == '') {
    $('.other-child .wkother:first').trigger('click');
  }
});

$(document).on('keyup', function (e) {
  if ((e.which == '13') && (user_login === '0')) {
    loginUser();
  };
});

$(document).on('click', '.wkorder', function () {
  $('.wkorder').removeClass('onfocus');
  var otype = $(this).addClass('onfocus').attr('otype');
  var order_html = '';
  if (otype == 1) {
    for (var i = 0; i < Object.keys(orders).length; i++) {
      order_html += '<div class="col-sm-2 order-display col-xs-4 cursor" order-id="' + orders[i]['order_id'] + '">';
      order_html += '  <div class="order-detail">';
      order_html += '    <div class="invoice-div">Order ID #' + orders[i]['order_id'] + '</div>';
      order_html += '    <div class="datetimeorder">';
      order_html += '      ' + orders[i]['time'] + '<br>';
      order_html += '      ' + orders[i]['date'];
      order_html += '    </div>';
      order_html += '  </div>';
      if (orders[i]['name']) {
        order_html += '  <div class="order-cname">' + orders[i]['name'] + '</div>';
      } else {
        order_html += '  <div class="order-cname">John Doe</div>';
      };
      order_html += '  <div class="table-responsive table-order">';
      order_html += '    <table class="width-100">';
      order_html += '      <tbody>';
      order_html += '       <tr>';
      order_html += '         <th>Status</th>';
      order_html += '         <td>' + orders[i]['status'] + '</td>';
      order_html += '       </tr>';
      order_html += '       <tr>';
      order_html += '         <th>Total</th>';
      order_html += '         <td>' + orders[i]['total'] + '</td>';
      order_html += '       </tr>';
      order_html += '      </tbody>';
      order_html += '    </table>';
      order_html += '  </div>';
      order_html += '</div>';
    };
  } else if (otype == 2) {
    var pos_cart_length = Object.keys(pos_cart).length;
    for (var i = 0; i < pos_cart_length; i++) {
      if (pos_cart[i]) {
        if (i == current_cart) {
          continue;
        };
        order_html += '<div class="col-sm-2 order-display col-xs-4 cursor">';
        order_html += '<div onclick="cart_list.select(' + i + ')" style="height: 91%;">';
        order_html += '  <div class="order-detail" style="height: 25px;">';
        order_html += '    <div class="datetimeorder">';
        if (pos_holds[i] && pos_holds[i]['time']) {
          order_html += '      <div class="hold-time">' + pos_holds[i]['time'] + '</div>';
        }
        if (pos_holds[i] && pos_holds[i]['date']) {
          order_html += '      <div class="hold-date">' + pos_holds[i]['date'] + '</div>';
        }
        order_html += '    </div>';
        order_html += '  </div>';
        if (pos_holds[i]['customer_name']) {
          order_html += '  <div class="order-cname">' + pos_holds[i]['customer_name'] + '</div>';
        } else {
          order_html += '  <div class="order-cname">' + guest_name + '</div>';
        };
        order_html += '  <span class="pull-right label label-info note-info"><i class="fa fa-info-circle"></i> Note </span>';
        if (pos_holds[i] && pos_holds[i]['note']) {
          order_html += '  <div class="hold-note">' + pos_holds[i]['note'] + '</div>';
        } else {
          order_html += '  <div class="hold-note">No note</div>';
        }
        order_html += '  <div class="item-detail">' + text_item_detail + '</div>';
        order_html += '  <div class="table-responsive table-order">';
        order_html += '    <table class="width-100">';
        order_html += '      <tbody>';
        for (var j = 0; j < Object.keys(pos_cart[i]).length; j++) {
          order_html += '      <tr>';
          order_html += '        <td>' + pos_cart[i][j]['name'] + '</td>';
          order_html += '        <td>x' + pos_cart[i][j]['quantity'] + '</td>';
          order_html += '        <td>' + pos_cart[i][j]['price'] + '</td>';
          order_html += '      </tr>';
          if (j == 4) {
            order_html += '<tr><td colspan="3" class="dot-css">...</td></tr>';
            break;
          };
        };
        order_html += '      </tbody>';
        order_html += '    </table>';
        order_html += '  </div>';
        order_html += '  </div>';
        order_html += '  <div class="alert-danger text-center" style="width: 100%; bottom: 0px; position: absolute;" onclick="deleteHoldCart(' + i + ');">';
        order_html += '    <i class="fa fa-trash">';
        order_html += '    </i>';
        order_html += '  </div>';
        order_html += '</div>';
      } else {
        ++pos_cart_length;
      }
    };
  } else if (otype == 3) {
    for (var i = 0; i < Object.keys(pos_orders).length; i++) {
      order_html += '<div class="col-sm-2 order-display col-xs-4 cursor" invoice-id="' + i + '" txn-id="' + pos_orders[i]['txn_id'] + '">';
      order_html += '  <div class="order-detail">';
      order_html += '    <div class="invoice-div">Txn ID #' + pos_orders[i]['txn_id'] + '</div>';
      order_html += '    <div class="datetimeorder">';
      order_html += '      ' + pos_orders[i]['time'] + ' <br>';
      order_html += '      ' + pos_orders[i]['date'];
      order_html += '    </div>';
      order_html += '  </div>';

      if (pos_orders[i]['customer'] && pos_orders[i]['cname']) {
        order_html += '  <div class="order-cname">' + pos_orders[i]['cname'] + '</div>';
      } else {
        order_html += '  <div class="order-cname">' + guest_name + '</div>';
      };
      order_html += '  <div class="item-detail">' + text_item_detail + '</div>';
      order_html += '  <div class="table-responsive table-order">';
      order_html += '    <table class="width-100">';
      order_html += '      <tbody>';
      for (var j = 0; j < Object.keys(pos_orders[i]['cart']).length; j++) {
        order_html += '        <tr>';
        order_html += '          <td>' + pos_orders[i]['cart'][j]['name'] + '</td>';
        order_html += '          <td>x' + pos_orders[i]['cart'][j]['quantity'] + '</td>';
        order_html += '          <td>' + pos_orders[i]['cart'][j]['price'] + '</td>';
        order_html += '        </tr>';
        if (j == 4) {
          order_html += '<tr><td colspan="3" class="dot-css">...</td></tr>';
          break;
        };
      };
      order_html += '      </tbody>';
      order_html += '    </table>';
      order_html += '  </div>';
      order_html += '</div>';
    };
    if (!(order_html == '')) {
      var sync_button = '<div class="row" style="margin: 0">';
      sync_button += ' <button class="btn buttons-sp pull-left" id="sync-orders">' + text_sync_order + '</button>';
      sync_button += '</div>';
      order_html = sync_button + order_html;
    }

  };
  if (order_html == '') {
    order_html = '<span class="col-xs-12 text-center">' + text_no_orders + '</span>';
  };
  $('#orders').html(order_html);
});

$(document).on('click', '.wkother', function () {
  var other_panel = $('#others');
  $('.wkother').removeClass('onfocus');
  var otype = $(this).addClass('onfocus').attr('otype');
  other_panel.html('');
  var other_html = '';
  if (otype == 1) {
    var product_count = total_product_count;
    for (var i = 0; i < product_count; i++) {
      if (pos_products[i]) {
        if (parseInt(pos_products[i]['quantity']) <= low_stock) {
          html = '<div class="col-sm-2 col-xs-6 product-select" product-id="' + pos_products[i]["product_id"] + '">';

          html += '  <img src="' + pos_products[i]["image"] + '" class="product-image" width="100%" height="100%">';
          html += '  <div class="col-xs-12 product-detail">';
          html += '    <b>' + pos_products[i]["name"] + '</b>';
          html += '  </div>';
          html += '</div>';
          other_panel.append(html);
        }
      } else {
        product_count++;
      }
    };
  } else if (otype == 2) {
    var product_count = total_product_count;
    var request_html = '', other_html = '';
    request_html += '<div class="table-responsive">';
    request_html += '  <table class="table table-bordered table-hover">';
    request_html += '    <thead class="btn-info">';
    request_html += '      <tr>';
    request_html += '        <td style="width: 1px;" class="text-center"><input type="checkbox" onclick="$(\'.request-check\').prop(\'checked\', this.checked);" /></td>';
    request_html += '        <td>Product Name</td>';
    request_html += '        <td style="width: 150px;">Quantity</td>';
    request_html += '        <td>Supplier</td>';
    request_html += '        <td>Comment</td>';
    request_html += '        <td>Requests</td>';
    request_html += '      </tr>';
    request_html += '    </thead>';
    request_html += '    <tbody>';
    for (var i = 0; i < product_count; i++) {
      if (pos_products[i]) {
        if (parseInt(pos_products[i]['quantity']) <= low_stock) {
          request_html += '<tr product-id="' + pos_products[i]['product_id'] + '">';
          request_html += '  <td><input type="checkbox" class="request-check form-control"></td>';
          request_html += '  <td>' + pos_products[i]['name'] + '</td>';
          request_html += '  <td><input type="number" class="form-control request-quantity" min="1" onkeypress="return validate(event, this, true)"></td>';
          request_html += '  <td><select class="form-control request-supplier">';
          var suppliers_length = Object.keys(pos_products[i]['suppliers']).length;
          if (suppliers_length) {
            for (var j = 0; j < suppliers_length; j++) {
              if (pos_products[i]['suppliers'][j]) {
                request_html += '    <option value="' + pos_products[i]['suppliers'][j]['id'] + '">' + pos_products[i]['suppliers'][j]['name'] + '</option>';
              } else {
                suppliers_length++;
              }
            }
          }
          request_html += '  </select></td>';
          request_html += '  <td><textarea class="form-control request-comment" placeholder="Comments"></textarea></td>';
          if (pos_products[i]['requests'] == 0) {
            request_html += '  <td>' + 'No requests made' + '</td>';
          } else {
            request_html += '  <td class="text-center"><span class="label label-info">' + pos_products[i]['requests'] + '</span></td>';
          }
          request_html += '</tr>';
        }
      } else {
        product_count++;
      }
    };
    request_html += '    </tbody>';
    request_html += '  </table>';
    request_html += '</div>';
    request_html += '<div class="col-sm-12 text-center">';
    request_html += '  <div class="form-group">';
    request_html += '    <label class="col-sm-2 control-label" for="input-extrainfo">Extra Info</label>';
    request_html += '    <div class="col-sm-10">';
    request_html += '      <textarea name="extra_info" placeholder="Extra Info" id="input-extrainfo" class="form-control" rows="3"></textarea>';
    request_html += '    </div>';
    request_html += '  </div>';
    request_html += '  <button class="buttons-sp" onclick="makeRequest();">Make Request</button>';
    request_html += '</div>';
    other_panel.append(request_html);
  } else if (otype == 3) {
    var request_html = '', other_html = '';
    request_html += '<div class="table-responsive">';
    request_html += '  <table class="table table-bordered table-hover">';
    request_html += '    <thead class="btn-info">';
    request_html += '      <tr>';
    request_html += '        <td>ID</td>';
    request_html += '        <td>Date</td>';
    request_html += '        <td class="text-center">Request Details</td>';
    request_html += '        <td>Status</td>';
    request_html += '      </tr>';
    request_html += '    </thead>';
    request_html += '    <tbody>';

    for (var i = 0; i < Object.keys(all_requests).length; i++) {
      request_html += '<tr>';
      request_html += '<td><b>' + all_requests[i]['request_id'] + '</b></td>';
      request_html += '<td>' + all_requests[i]['date_added'] + '</td>';
      request_html += '<td class="text-center">';
      request_html += '<div class="table-responsive">';
      request_html += '  <table class="table table-bordered table-hover">';
      request_html += '    <thead class="btn-info">';
      request_html += '      <tr>';
      request_html += '        <td>Product</td>';
      request_html += '        <td>Supplier</td>';
      request_html += '        <td>Quantity</td>';
      request_html += '      </tr>';
      request_html += '    </thead>';
      request_html += '    <tbody>';
      for (var j = 0; j < Object.keys(all_requests[i]['details']).length; j++) {
        request_html += '<tr>';
        request_html += '<td>' + all_requests[i]['details'][j]['name'] + '</td>';
        request_html += '<td>' + all_requests[i]['details'][j]['sname'] + '</td>';
        request_html += '<td>' + all_requests[i]['details'][j]['quantity'] + '</td>';
        request_html += '</tr>';
      }
      request_html += '    </tbody>';
      request_html += '  </table>';
      request_html += '</div>';
      request_html += '</td>';
      request_html += '<td>' + all_requests[i]['status'] + '</td>';
      request_html += '</tr>';
    }

    request_html += '    </tbody>';
    request_html += '  </table>';
    request_html += '</div>';

    other_panel.append(request_html);
  }
});

$(document).on('click', '.close-it', function () {
  $('.wksidepanel').removeClass('onfocus');
  $('.parents').removeClass('panel-show');
  $('.sidepanel').removeClass('sidepanel-show');
  if ($(this).parent().hasClass('payment-child2')) {
    $('.cash-payment').addClass('hide');
  }
});

$(document).on('click', '.order-display', function () {
  if ($(this).children().hasClass('alert-danger')) {
    return;
  }
  $('.order-loader').removeClass('hide');
  $('#sidepanel-inner').css('display', 'none');

  var order_id = $(this).attr('order-id');
  var invoice_id = $(this).attr('invoice-id');
  var txn_id = $(this).attr('txn-id');
  var order_html = '', print_order_html = '';
  var total_quantity = 0;

  if (order_id) {
    $('#order-address').html(order_products[order_id]['address']);
    $('.order-date').html(order_products[order_id]['date']);
    $('.order-time').html(order_products[order_id]['time']);
    $('.opayment').html(order_products[order_id]['payment_method']);
    $('.onote').html(order_products[order_id]['note']);
    $('#cashier-name').html(order_products[order_id]['username']);
    $('.order-txn').text(text_order_id);
    $('.oid').html(order_id);

    for (var i = 0; i < Object.keys(order_products[order_id]['products']).length; i++) {
      total_quantity += parseInt(order_products[order_id]['products'][i]['quantity']);
      order_html += '<tr>';
      order_html += '  <td class="text-left">' + order_products[order_id]['products'][i]['name'] + '</td>';
      order_html += '  <td class="text-center">x' + order_products[order_id]['products'][i]['quantity'] + '</td>';
      order_html += '  <td class="text-left">' + order_products[order_id]['products'][i]['total'] + '</td>';
      order_html += '<tr>';
      print_order_html += '<tr>';
      print_order_html += '  <td style="text-align: left">' + order_products[order_id]['products'][i]['name'] + '</td>';
      print_order_html += '  <td>' + order_products[order_id]['products'][i]['quantity'] + '</td>';
      print_order_html += '  <td styele="text-align:left !important">' + order_products[order_id]['products'][i]['price'] + '</td>';
      print_order_html += '  <td style="text-align: left !important">' + order_products[order_id]['products'][i]['total'] + '</td>';
      print_order_html += '</tr>';
    };
    $('#oitem-body').html(order_html);
    $('#receiptProducts').html(print_order_html);
    var total_html = '';
    var print_total_html = '';
    var order_total;
    for (var i = 0; i < Object.keys(order_products[order_id]['totals']).length; i++) {
      if (order_products[order_id]['totals'][i]['title'] == 'Total') {
        order_total = order_products[order_id]['totals'][i]['text'];
        continue;
      };
      total_html += '<tr>';
      total_html += '  <td class="text-left">' + order_products[order_id]['totals'][i]['title'] + '</td>';
      total_html += '  <td class="text-left">' + order_products[order_id]['totals'][i]['text'] + '</td>';
      total_html += '<tr>';
      print_total_html += '<tr>';
      if (i == 0) {
        print_total_html += '  <td>Total Quantity</td>';
        print_total_html += '  <td><b>' + total_quantity + '</b></td>';
        print_total_html += '  <td>' + order_products[order_id]['totals'][i]['title'] + '</td>';
        print_total_html += '  <td style="text-align: left">' + order_products[order_id]['totals'][i]['text'] + '</td>';
      } else {
        print_total_html += '  <td></td>';
        print_total_html += '  <td></td>';
        print_total_html += '  <td>' + order_products[order_id]['totals'][i]['title'] + '</td>';
        print_total_html += '  <td style="text-align: left">' + order_products[order_id]['totals'][i]['text'] + '</td>';
      }
      print_total_html += '</tr>';
    };
    $('#oTotals').html(total_html);
    $('#total-quantity-text').text('');
    $('#total-quantity').text('');
    $('#print-totals').html(print_total_html);
    $('.oTotal').html(order_total);
  };

  if (invoice_id) {
    $('.order-date').html(pos_orders[invoice_id]['date']);
    $('.order-time').html(pos_orders[invoice_id]['time']);
    if (pos_orders[invoice_id]['payment'] == 'cash') {
      $('.opayment').html(cash_payment_title);
    } else {
      $('.opayment').html('');
    }
    $('.onote').html(pos_orders[invoice_id]['order_note']);
    $('#cashier-name').html(pos_orders[invoice_id]['cashier']);
    $('.order-txn').text('Txn ID');
    $('.oid').html(txn_id);
    var uf_total = 0;
    for (var i = 0; i < Object.keys(pos_orders[invoice_id]['cart']).length; i++) {
      total_quantity += parseInt(pos_orders[invoice_id]['cart'][i]['quantity']);
      order_html += '<tr>';
      order_html += '  <td class="text-left">' + pos_orders[invoice_id]['cart'][i]['name'] + '</td>';
      order_html += '  <td class="text-center">x' + pos_orders[invoice_id]['cart'][i]['quantity'] + '</td>';
      order_html += '  <td class="text-right">' + pos_orders[invoice_id]['cart'][i]['total'] + '</td>';
      order_html += '<tr>';
      print_order_html += '<tr>';
      print_order_html += '  <td style="text-align: left">' + pos_orders[invoice_id]['cart'][i]['name'] + '</td>';
      print_order_html += '  <td>' + pos_orders[invoice_id]['cart'][i]['quantity'] + '</td>';
      print_order_html += '  <td>' + pos_orders[invoice_id]['cart'][i]['price'] + '</td>';
      print_order_html += '  <td style="text-align: right">' + pos_orders[invoice_id]['cart'][i]['total'] + '</td>';
      print_order_html += '</tr>';
      uf_total += parseFloat(pos_orders[invoice_id]['cart'][i]['uf_total']);
    };
    $('#oitem-body').html(order_html);
    $('#receiptProducts').html(print_order_html);
    $('#total-quantity-text').text('Total Quantity');
    $('#total-quantity').text(total_quantity);
    var total_html = '';
    var print_total_html = '';

    if (pos_orders[invoice_id]['discount']['discount']) {
      var cdiscount = parseFloat(pos_orders[invoice_id]['discount']['discount']).toFixed(2);
    } else {
      var cdiscount = 0;
    }

    uf_total = parseFloat(uf_total).toFixed(2);

    if (symbol_position == 'L') {
      var subtotal = currency_code + uf_total;
      var discount = currency_code + cdiscount;
      var total = currency_code + parseFloat(uf_total - cdiscount).toFixed(2);
    } else {
      var subtotal = uf_total + currency_code;
      var discount = cdiscount + currency_code;
      var total = parseFloat(uf_total - cdiscount).toFixed(2) + currency_code;
    }

    total_html += '<tr>';
    total_html += '  <td class="text-left">' + 'Sub-Total' + '</td>';
    total_html += '  <td class="text-right">' + subtotal + '</td>';
    total_html += '<tr>';
    print_total_html += '<tr>';
    print_total_html += '  <td></td>';
    print_total_html += '  <td></td>';
    print_total_html += '  <td>' + 'Sub-Total' + '</td>';
    print_total_html += '  <td style="text-align: right;">' + subtotal + '</td>';
    print_total_html += '<tr>';

    if (cdiscount) {
      total_html += '<tr>';
      total_html += '  <td class="text-left">' + pos_orders[invoice_id]['discount']['name'] + '</td>';
      total_html += '  <td class="text-right">' + discount + '</td>';
      total_html += '<tr>';
      print_total_html += '<tr>';
      print_total_html += '  <td></td>';
      print_total_html += '  <td></td>';
      print_total_html += '  <td>' + pos_orders[invoice_id]['discount']['name'] + '</td>';
      print_total_html += '  <td style="text-align: right;">' + discount + '</td>';
      print_total_html += '<tr>';
    }

    $('#print-totals').html(print_total_html);
    $('#oTotals').html(total_html);
    $('.oTotal').html(total);
  };
  setTimeout(function () {
    $('#sidepanel-inner').css('display', 'block');
    $('.order-loader').addClass('hide');
  }, 800);

  if ($(window).width() < 993) {
    $.toaster({
      priority: 'danger',
      message: error_mobile_view,
      timeout: 5000
    });
  }
});

$(document).on('click', '.button-accounts', function () {
  var stype = $(this).attr('stype');
  if (offline) {
    $.toaster({
      priority: 'danger',
      message: error_save_setting,
      timeout: 5000
    });
    return;
  }
  if (stype == 'basic') {
    var postDetails = $('.basic-account input[type=\'text\'], .basic-account input[type=\'password\']');

    $.ajax({
      url: 'index.php?route=wkpos/wkpos/updateProfile',
      dataType: 'json',
      type: 'post',
      data: postDetails,
      beforeSend: function () {
        $('#loader').css('display', 'block');
        $('.has-error').removeClass('has-error');
        $('.text-danger').remove();
      },
      success: function (json) {
        if (json['success']) {
          $('.logger-name').text(postDetails[0].value + ' ' + postDetails[1].value);
          $('#account-ppwd').val('');
          $('#account-npwd').val('');
          $('#account-cpwd').val('');
          $.toaster({
            priority: 'success',
            message: json['success'],
            timeout: 5000
          });
          $('.wksidepanel').removeClass('onfocus');
          $('.parents').removeClass('panel-show');
        };
        if (json['error']) {
          $.each(json['errors'], function (key, value) {
            var selector = key.replace('_', '-');
            $('#' + selector).after('<span class="text-danger"><i class="fa fa-exclamation-triangle"></i> ' + value + '</span>').parent().parent().addClass('has-error');
          });
          $.toaster({
            priority: 'danger',
            message: json['error'],
            timeout: 5000
          });
        };
        $('#loader').css('display', 'none');
      }
    });
  };
  if (stype == 'other') {
    var language = $('#input-language').val();
    var new_currency = $('#input-currency').val();
    $.ajax({
      url: 'index.php?route=wkpos/wkpos/changeSettings',
      dataType: 'json',
      type: 'post',
      data: {language: language, currency: new_currency},
      beforeSend: function () {
        $('#loader').css('display', 'block');
      },
      success: function (json) {
        if (json['success']) {
          currency_update = true;
          start = 0;
          getPopularProducts();
          currency = json['currency'];
          currency_code = json['currency_code'];
          symbol_position = json['symbol_position'];

          $('.currency strong').text(currency_code);

          if (symbol_position == 'L') {
            $('.input-group1 .currency:first').css('display', 'table-cell');
            $('.input-group1 .currency:last').css('display', 'none');
            $('.input-group2 .currency:first').css('display', 'table-cell');
            $('.input-group2 .currency:last').css('display', 'none');
            $('.input-group3 .currency:first').css('display', 'table-cell');
            $('.input-group3 .currency:last').css('display', 'none');
          }

          if (symbol_position == 'R') {
            $('.input-group1 .currency:first').css('display', 'none');
            $('.input-group1 .currency:last').css('display', 'table-cell');
            $('.input-group2 .currency:first').css('display', 'none');
            $('.input-group2 .currency:last').css('display', 'table-cell');
            $('.input-group3 .currency:first').css('display', 'none');
            $('.input-group3 .currency:last').css('display', 'table-cell');
          }

          $.toaster({
            priority: 'success',
            message: json['success'],
            timeout: 5000
          });

          $('.wksidepanel').removeClass('onfocus');
          $('.parents').removeClass('panel-show');
        };
      }
    });
  };
});

function updateCartCurrency() {
  for (var cart in pos_cart) {
    if (pos_cart.hasOwnProperty(cart)) {
      for (var product in pos_cart[cart]) {
        if (cart.hasOwnProperty(product)) {
          var cart_product = pos_cart[cart][product];
          if (pos_products[cart_product.product_id]) {
            if (pos_products[cart_product.product_id]['special']) {
              var price = pos_products[cart_product.product_id]['special'];
            } else {
              var price = pos_products[cart_product.product_id]['price_uf'];
            }
            cart_product.uf = price;
            cart_product.price = pos_products[cart_product.product_id]['price'];
          }
        }
      }
    }
  }
}

var pwidth = 0;
var psegments, orders_count;
$(document).on('click', '#sync-orders', function () {
  if (offline) {
    $.toaster({
      priority: 'danger',
      message: error_sync_orders,
      timeout: 5000
    });
    return;
  };
  orders_count = Object.keys(pos_orders).length;
  pwidth = 0;
  psegments = 100/orders_count;
  $('#loader').css('display', 'block');
  $('.progress').removeClass('hide');
  $('#loading-text').removeClass('hide').text(text_sync_order);
  $('.progress-bar').addClass('progress-bar-dan').css('width', 0);
  for (var i = 0; i < orders_count; i++) {
    syncOfflineOrders(i);
  };
});

function syncOfflineOrders (i) {
  setTimeout(function () {
    $.ajax({
      url: 'index.php?route=wkpos/order/addOrder',
      dataType: 'json',
      type: 'post',
      data: {cart: pos_orders[i]['cart'], payment_method: pos_orders[i]['payment'], customer_id: pos_orders[i]['customer'], offline: 1, user_id: pos_orders[i]['user_login'], order_note: pos_orders[i]['order_note'], txn_id: pos_orders[i]['txn_id'], discount: pos_orders[i]['discount'], currency: pos_orders[i]['currency']},
      beforeSend: function () {
      },
      success: function (json) {
        pwidth += psegments;
        $('.progress-bar').css('width', pwidth + '%');

        if ((i + 1) == orders_count) {
          pos_orders = {};
          if (localStorage) {
            localStorage.pos_orders = JSON.stringify(pos_orders);
          };
          $('#orders').html('<span class="col-xs-12 text-center">' + text_no_orders + '</span>');
          setTimeout(function () {
            $.toaster({
              priority: 'success',
              message: json['success'],
              timeout: 3000
            });
            setTimeout(function () {
              getAllOrders();
            }, 1000);
            $('#loader').css('display', 'none');
            $('.progress').addClass('hide');
            $('#loading-text').addClass('hide');
          }, 1000);
        };
      }
    });
  }, i * 1000);
}

function printBill() {
  $('#toaster').css('display', 'none');
  $('#top-div').css('display', 'none');
  $('body .bootstrap-datetimepicker-widget').css('display', 'none');
  $('#printBill').css('display', 'block');
  window.print();
  $('#toaster').css('display', 'block');
  $('#top-div').css('display', 'block');
  $('#printBill').css('display', 'none');
  $('#loader').css('display', 'none');
}

$(document).on('click', '#hold-carts', function () {
  $('#button-order').trigger('click');
  $('.order-child .wkorder:first').next().trigger('click');
});

function accountSettings (thisthis) {
  $('#button-account').trigger('click');
  $('.wkaccounts:first').trigger('click');
  $(thisthis).addClass('onfocus');
}

function logout () {
  if (offline) {
    $.toaster({
      priority: 'success',
      message: text_success_logout,
      timeout: 3000
    });
    $('#loginModalParent').css('display', 'block');
  } else {
    location = 'index.php?route=wkpos/wkpos/logout';
  };
}

$(document).on('click', '#resumeSession', function () {
  $('#clockin').css('display', 'none');
  getPopularProducts();
});

$(document).on('click', '#startSession', function () {
  localStorage.pos_cart = '';
  localStorage.pos_holds = '';
  localStorage.pos_products = '';
  localStorage.pos_taxes = '';
  // localStorage.pos_orders = '';
  localStorage.pos_remove_id = '';
  $('#clockin').css('display', 'none');
  getPopularProducts();
});

$(document).on('click', '#show-cart', function () {
  if ($(window).width() < 992) {
    var cartpanel = $('#cart-panel');
    if (cartpanel.attr('right-pos') == 0) {
      cartpanel.css('right', '-91.66%');
      cartpanel.attr('right-pos', 91.66);
    } else {
      cartpanel.css('right', 0);
      cartpanel.attr('right-pos', 0);
    }
  }
});

function holdOrder() {
  var current_cart_length = Object.keys(pos_cart[current_cart]).length;
  if (!current_cart_length) {
    $.toaster({
      priority: 'warning',
      message: text_empty_hold,
      timeout: 3000
    });
    $('.in').trigger('click');
    return;
  }
  var note = $('#holdNote').val();
  cart_list.add(note);
  $('.in').trigger('click');
  $('#holdNote').val('');
  $('.wkorder:nth-child(2)').trigger('click');
}

function checkTime(i) {
  if (i < 10) {i = "0" + i};  // add zero in front of numbers < 10
  return i;
}

function getCurrentDate() {
  var d = new Date();
  var date = d.getDate();
  var m = d.getMonth();
  var y = d.getFullYear();
  var month_list = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var full_date = month_list[m] + ' ' + date + ' ' + y;
  return full_date;
}

function getCurrentTime() {
  var today = new Date();
  var h = today.getHours();
  var m = today.getMinutes();
  var s = today.getSeconds();
  m = checkTime(m);
  s = checkTime(s);
  var full_time = h + ":" + m + ":" + s;
  return full_time;
}

function deleteHoldCart(cart_id) {
  cart_list.delete(1, cart_id);
  $('.wkorder:nth-child(2)').trigger('click');
}

$(document).on('change', 'select[name=\'country_id\']', function() {
  $.ajax({
    url: 'index.php?route=account/account/country&country_id=' + this.value,
    dataType: 'json',
    beforeSend: function() {
      $('select[name=\'country_id\']').after(' <i class="fa fa-circle-o-notch fa-spin"></i>');
    },
    complete: function() {
      $('.fa-spin').remove();
    },
    success: function(json) {
      if (json['postcode_required'] == '1') {
        $('input[name=\'postcode\']').parent().parent().addClass('required');
      } else {
        $('input[name=\'postcode\']').parent().parent().removeClass('required');
      }

      html = '<option value="">' + text_select + '</option>';

      if (json['zone'] && json['zone'] != '') {
        for (i = 0; i < json['zone'].length; i++) {
          html += '<option value="' + json['zone'][i]['zone_id'] + '"';

          html += '>' + json['zone'][i]['name'] + '</option>';
        }
      } else {
        html += '<option value="0" selected="selected">' + text_none + '</option>';
      }

      $('select[name=\'zone_id\']').html(html);
    },
    error: function(xhr, ajaxOptions, thrownError) {
      alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
    }
  });
});

function registerCustomer(thisthis, select) {
  var customer_data = $('.addCustomer input, .addCustomer select');
  $.ajax({
    url: 'index.php?route=wkpos/customer/addCustomer',
    data: customer_data,
    dataType: 'json',
    type: 'post',
    beforeSend: function () {
      thisthis.text('loading');
      $('.has-error').removeClass('has-error');
      $('.text-danger').remove();
    },
    success: function (json) {
      if (json['success']) {
        if (select) {
          customer_id = json['customer_id'];
          customer_name = $('#input-customer-firstname').val() + ' ' + $('#input-customer-lastname').val();
          $('#customer-name').text(customer_name);
          $.toaster({
            priority: 'success',
            message: text_cust_add_select,
            timeout: 3000
          });
        } else {
          $.toaster({
            priority: 'success',
            message: json['success'],
            timeout: 3000
          });
        }
        getAllCustomers(true);
        $('.in').trigger('click');
        $('#input-customer-firstname').val('');
        $('#input-customer-lastname').val('');
        $('#input-email').val('');
        $('#input-telephone').val('');
        $('#input-address-1').val('');
        $('#input-city').val('');
        $('#input-postcode').val('');
        $('#input-country').val('');
        $('#input-zone').val('');
      }
      if (json['error']) {
        if (json['firstname']) {
          $('#input-customer-firstname').after('<div class="text-danger">' + json['firstname'] + '</div>').parent().parent().addClass('has-error');
        }
        if (json['lastname']) {
          $('#input-customer-lastname').after('<div class="text-danger">' + json['lastname'] + '</div>').parent().parent().addClass('has-error');
        }
        if (json['email']) {
          $('#input-email').after('<div class="text-danger">' + json['email'] + '</div>').parent().parent().addClass('has-error');
        }
        if (json['telephone']) {
          $('#input-telephone').after('<div class="text-danger">' + json['telephone'] + '</div>').parent().parent().addClass('has-error');
        }
        if (json['address_1']) {
          $('#input-address-1').after('<div class="text-danger">' + json['address_1'] + '</div>').parent().parent().addClass('has-error');
        }
        if (json['city']) {
          $('#input-city').after('<div class="text-danger">' + json['city'] + '</div>').parent().parent().addClass('has-error');
        }
        if (json['postcode']) {
          $('#input-postcode').after('<div class="text-danger">' + json['postcode'] + '</div>').parent().parent().addClass('has-error');
        }
        if (json['country']) {
          $('#input-country').after('<div class="text-danger">' + json['country'] + '</div>').parent().parent().addClass('has-error');
        }
        if (json['zone']) {
          $('#input-zone').after('<div class="text-danger">' + json['zone'] + '</div>').parent().parent().addClass('has-error');
        }
      }

      if (select) {
        thisthis.html('<strong>' + text_register_select + '</strong>');
      } else {
        thisthis.html('<strong>' + text_register + '</strong>');
      }
    },
    error: function () {
      if (select) {
        thisthis.html('<strong>' + text_register_select + '</strong>');
      } else {
        thisthis.html('<strong>' + text_register + '</strong>');
      }
    }
  });
}

function printInvoice() {
  if (!offline) {
    $('.wkorder:first').trigger('click');
    $('#orders .order-display:first').trigger('click');
  } else {
    $('.wkorder:nth-child(3)').trigger('click');
    $('#orders .order-display:last').trigger('click');
  }
  printBill();
  $('#postorder').css('display', 'none');
}

$(document).on('click', '.barcode-scan', function () {
  $('#bar-code').val('');
  setTimeout(function () {
    $('#bar-code').focus();
  }, 500);
});
function bar_code_search_with_model_sku(product)
{



  var product_count = total_product_count;
  $.each(pos_products, function (i, val) {

    if (pos_products[i]) {

      if (!(show_lowstock_prod == 1) && (pos_products[i]['quantity'] < 1)) {
      return;
      }

      if (pos_products[i]["model"].toLowerCase().search(product) != '-1' || pos_products[i]["sku"].toLowerCase().search(product) != '-1') {
          product_id =pos_products[i]['product_id'];

          if (pos_products[product_id]['option']) {
            var option = 'true';
            $('.in').trigger('click');
          } else {
            var option = 'false';
          }

        var options = {
          product_id: product_id,
          option: option,
          thisthis: $(this)
        };

        addToCart(false, options);

$("#barcodeScan").modal('hide');
        return;
        }
        $(this).val('');


   }

 });
$("#barcodeScan").modal('hide');
}
$(document).on('keyup', '#bar-code', function (key) {
  if (key.which == 13) {
    var product = $(this).val();
    bar_code_search_with_model_sku(product);
    var product_id = parseInt(product.replace('wkpos', ''));

    if (!(product == product_id) && pos_products[product_id]) {
      if (pos_products[product_id]['option']) {
        var option = 'true';
        $('.in').trigger('click');
      } else {
        var option = 'false';
      }

      var options = {
        product_id: product_id,
        option: option,
        thisthis: $(this)
      };

      addToCart(false, options);
    } else {
      $.toaster({
        priority: 'warning',
        message: text_no_product,
        timeout: 5000
      });
    }
    $(this).val('');
  }
});

$(document).on('click', '#barcodeScan', function () {
    $('#bar-code').focus();
});

$(document).on('keyup', '#fixedDisc,#percentDisc', function () {
  var fixedDisc = $('#fixedDisc').val();
  var percentDisc = $('#percentDisc').val();
  var fixed = parseFloat(fixedDisc).toFixed(2);
  var percent = parseFloat(percentDisc).toFixed(2);
  if (fixed == 'NaN') {
    fixed = 0;
  }
  if (percent == 'NaN') {
    percent = 0;
  }

  var percentDiscount = (uf_sub_total * percent)/100;
  var total_discount = (parseFloat(percentDiscount) + parseFloat(fixed)).toFixed(2);
  totalDiscount = total_discount;
  $('#total-discount').text(total_discount);
});

$(document).on('click', '#addDiscountbtn', function () {
  $('#fixedDisc').trigger('keyup');

  if (totalDiscount > parseFloat(uf_sub_total)) {
    $.toaster({
      priority: 'warning',
      message: error_cart_discount,
      timeout: 3000
    });
    uf_total_discount = totalDiscount = 0;
  } else {
    uf_total_discount = totalDiscount;

    if (!discountApply && (totalDiscount > 0)) {
      $.toaster({
        priority: 'success',
        message: text_success_add_disc,
        timeout: 3000
      });
    }

    if (totalDiscount > 0) {
      $('#discrow').css('display', '');
      discountApply = 1;
    } else {
      $('#discrow').css('display', 'none');
      discountApply = 0;
    }

    $('.in').trigger('click');
  }
  printCart();
});

$(document).on('click', '#removeDiscount', function () {
  uf_total_discount = 0;
  $('#discrow').css('display', 'none');
  printCart();
  $.toaster({
    priority: 'success',
    message: text_success_rem_disc,
    timeout: 3000
  });
  $('.in').trigger('click');
  discountApply = 0;
});

$(document).on('click', '#addCouponbtn', function () {
  var coupon_code = $('#coupon-code').val();
  $('.in').trigger('click');

  if (offline) {
    $.toaster({
      priority: 'warning',
      message: error_coupon_offline,
      timeout: 3000
    });
  } else {
    $.ajax({
      url: 'index.php?route=wkpos/order/applyCoupon',
      dataType: 'json',
      type: 'post',
      data: {coupon: coupon_code, customer: customer_id, subtotal: uf_sub_total, cart: pos_cart[current_cart]},
      beforeSend: function () {
        $('#loader').css('display', 'block');
      },
      success: function (json) {
        if (json['success']) {
          $.toaster({
            priority: 'success',
            message: json['success'],
            timeout: 3000
          });
          $('#couprow').css('display', 'none');
          if (json['coupon']['type']) {
            var total = uf_sub_total;
            var status = false;

            if (!((json['coupon']['product']).length == 0)) {
              total = 0;

              for (var i = 0; i < Object.keys(pos_cart[current_cart]).length; i++) {
                if ($.inArray( pos_cart[current_cart][i]['product_id'], json['coupon']['product'] ) != '-1') {
                  total += parseFloat(pos_cart[current_cart][i]['uf_total']);
                  status = true;
                }
              }
            } else {
              status = true;
            }

            if (json['coupon']['type'] == 'P') {
              coupon_disc = parseFloat((total * json['coupon']['discount']) / 100);
            }
            if (status && (json['coupon']['type'] == 'F')) {
              coupon_disc = parseFloat(json['coupon']['discount']);
            }

            if (coupon_disc > total) {
              coupon_disc = total;
            }
            coupon = json['coupon']['code'];

            $('#couprow').css('display', '');
          }
        };
        if (json['error']) {
          $.toaster({
            priority: 'danger',
            message: json['error'],
            timeout: 3000
          });
        };
        $('#loader').css('display', 'none');
        printCart();
      }
    });
  }
});

$(document).on('click', '#removeCoupon', function () {
  coupon_disc = 0;
  coupon = '';
  $('#couprow').css('display', 'none');
  printCart();
  $.toaster({
    priority: 'success',
    message: text_coupon_remove,
    timeout: 3000
  });
  $('#coupon-code').val('');
  $('.in').trigger('click');
});

function toggleFullScreen() {
  if ((document.fullScreenElement && document.fullScreenElement !== null) ||
   (!document.mozFullScreen && !document.webkitIsFullScreen)) {
    if (document.documentElement.requestFullScreen) {
      document.documentElement.requestFullScreen();
    } else if (document.documentElement.mozRequestFullScreen) {
      document.documentElement.mozRequestFullScreen();
    } else if (document.documentElement.webkitRequestFullScreen) {
      document.documentElement.webkitRequestFullScreen(Element.ALLOW_KEYBOARD_INPUT);
    }
  } else {
    if (document.cancelFullScreen) {
      document.cancelFullScreen();
    } else if (document.mozCancelFullScreen) {
      document.mozCancelFullScreen();
    } else if (document.webkitCancelFullScreen) {
      document.webkitCancelFullScreen();
    }
  }
}
function makeRequest() {
  if (offline) {
    $.toaster({
      priority: 'danger',
      message: error_request_offline,
      timeout: 5000
    });
    return;
  }

  var error = '', request_data = [], l = 0, check = 0;
  $('.has-error').removeClass('has-error');
  $('.text-danger').remove();

  $('#others .request-check').each(function(index) {
    if ($(this).is(':checked')) {
      check = 1;
      var parent_tr = $(this).parent().parent();
      var current_quant = parent_tr.children('td:nth-child(3)').children('.request-quantity');
      var quantity = current_quant.val();

      if (parseInt(quantity)) {
        var current_supplier = parent_tr.children('td:nth-child(4)').children('.request-supplier');
        var supplier_id = current_supplier.val();
        var product_id = parent_tr.attr('product-id');

        if (supplier_id && pos_products[product_id] && pos_products[product_id]['suppliers'][supplier_id]) {
          var supplier = pos_products[product_id]['suppliers'][supplier_id];

          if (quantity < parseInt(supplier['min']) || quantity > parseInt(supplier['max'])) {
            error = 1;
            current_quant.after('<span class="text-danger">' + error_supplier_range.replace('%range%', supplier['min'] + '-' + supplier['max']) + '</span>').parent().addClass('has-error');
          } else {
            var comment = parent_tr.children('td:nth-child(5)').children('.request-comment').val();
            var data = {
              'sid': supplier_id,
              'pid': product_id,
              'quant': quantity,
              'comm': comment
            };
            request_data[l++] = data;
          }
        } else {
          error = 1;
          current_supplier.after('<span class="text-danger">' + error_supplier + '</span>').parent().addClass('has-error');
        }
      } else {
        error = 1;
        current_quant.after('<span class="text-danger">' + text_no_quantity_added + '</span>').parent().addClass('has-error');
      }
    }
  });

  if (error || !check) {
    if (!check) {
      $.toaster({
        priority: 'danger',
        message: error_select_product,
        timeout: 5000
      });
    } else {
      $.toaster({
        priority: 'danger',
        message: error_warning,
        timeout: 5000
      });
    }
  } else {
    $.ajax({
      url: 'index.php?route=wkpos/supplier/addRequest',
      dataType: 'json',
      type: 'post',
      data: {request_data: request_data, comment: $('#input-extrainfo').val()},
      beforeSend: function () {
        $('#loader').css('display', 'block');
      },
      success: function (json) {
        if (json['success']) {
          $.toaster({
            priority: 'success',
            message: json['success'],
            timeout: 5000
          });

          // $('.wkother:nth-child(2)').trigger('click');
          $('.close-it').trigger('click');
          getAllProducts();
        };
        if (json['error']) {
          $.toaster({
            priority: 'danger',
            message: json['error'],
            timeout: 5000
          });
        };
        $('#loader').css('display', 'none');
      }
    });
  }
}

function getAllSuppliers() {
  $.ajax({
    url: 'index.php?route=wkpos/supplier',
    dataType: 'json',
    type: 'get',
    success: function (json) {
      all_suppliers = json['suppliers'];
    }
  });
}

function getRequestHistory() {
  $.ajax({
    url: 'index.php?route=wkpos/supplier/getRequestHistory',
    dataType: 'json',
    type: 'get',
    success: function (json) {
      all_requests = json['requests'];
      $('.wkother:nth-child(3)').trigger('click');
    }
  });
}

function priceUpdate(i) {
  var current_price = pos_cart[current_cart][i]['uf'];
  var product_name = pos_cart[current_cart][i]['name'];
  $('#update-label').text(product_name);
  $('#update-price').prop('value', current_price);
  $('#cart-index').val(i);
}

$(document).on('click', '#updatePricebtn', function () {
  var new_price = $('#update-price').val();
  if (isNaN(new_price)) {
    $.toaster({
      priority: 'danger',
      message: error_price,
      timeout: 3000
    });
    return false;
  }
  var cart_index = $('#cart-index').val();
  pos_cart[current_cart][cart_index]['uf'] = new_price;
  pos_cart[current_cart][cart_index]['special'] = formatPrice(new_price);
  pos_cart[current_cart][cart_index]['custom'] = 1;
  $('.in').trigger('click');
  $.toaster({
    priority: 'success',
    message: text_success_price_up,
    timeout: 3000
  });
  cartLocalStorage();
  printCart();
});

$(document).on('click', '#cancelPriceUp', function () {
  var cart_index = $('#cart-index').val();
  pos_cart[current_cart][cart_index]['uf'] = pos_products[pos_cart[current_cart][cart_index]['product_id']]['price_uf'];
  pos_cart[current_cart][cart_index]['special'] = 0;
  delete pos_cart[current_cart][cart_index]['custom'];
  $('.in').trigger('click');
  $.toaster({
    priority: 'success',
    message: text_price_remove,
    timeout: 3000
  });
  cartLocalStorage();
  printCart();
});

$(document).on('click', '#addProductbtn', function () {
  var product_name = $.trim($('#addProduct input[name="product_name"]').val());
  var product_price = $.trim($('#addProduct input[name="product_price"]').val());
  var product_quantity = $.trim($('#addProduct input[name="product_quantity"]').val());
  var error = {};
  if (product_name.match(/<script>|alert|onmouseover=|onclick=|onmouseenter=|onblur=|onfocusin=|onfocusout=/)) {
    $.toaster({
      priority: 'danger',
      message: error_script,
      timeout: 3000
    });
    return false;
  }
  if (!(product_name && product_name.length > 3)) {
    error.product_name = error_product_name;
  }
  if (!(product_price && !isNaN(product_price) && product_price>0)) {
    error.product_price = error_product_price;
  }
  if (!(product_quantity && !isNaN(product_quantity) && (product_quantity.indexOf(".") == -1) && product_quantity>0)) {
    error.product_quantity = error_product_quant;
  }

  if (Object.keys(error).length > 0) {
    for (var value in error) {
      if (error.hasOwnProperty(value)) {
        $.toaster({
          priority: 'danger',
          message: error[value],
          timeout: 3000
        });
      }
    }
  } else {
    var add_product = {};
    product_price = parseFloat(product_price).toFixed(2);
    product_quantity = parseInt(product_quantity);
    add_product.name = product_name;
    add_product.model = product_name;
    add_product.options = [];
    add_product.price = formatPrice(product_price);
    add_product.product_id = 0;
    add_product.product_index = 0;
    add_product.quantity = product_quantity;
    add_product.remove = pos_remove_id++;
    add_product.special = 0;
    add_product.total = formatPrice(product_price * product_quantity);
    add_product.uf = product_price;
    add_product.uf_total = product_price * product_quantity;
    add_product.custom = 1;

    var adding = Object.keys(pos_cart[current_cart]).length;
    pos_cart[current_cart][adding] = add_product;
    $('.in').trigger('click');
    $.toaster({
      priority: 'success',
      message: text_product_success,
      timeout: 3000
    });
    cartLocalStorage();
    printCart();
  }
});

$(document).ready(function () {
  var tendered_box = document.getElementById('amount-tendered');
  tendered_box.onpaste = function(e) {
    e.preventDefault();
  }
});
