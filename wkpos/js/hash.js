  function setHash(filter_type, filter_id) {
    var flag = true;
    var new_hash = '';
    var hash_url = window.location.hash.substr(1);
    if (!(hash_url == '')) {
      hash_url += '/';
    }

    var hash_array = hash_url.split('/');
    for (var i = 0; i < hash_array.length; i++) {
      if (hash_array[i] == filter_type) {
        hash_array[i+1] = filter_id;
        flag = false;
      }
      if (!(new_hash == '') && !(hash_array[i] == '')) {
        new_hash += '/' + hash_array[i];
      } else {
        new_hash += hash_array[i];
      }
    }

    if (flag) {
      window.location.hash = hash_url + filter_type + '/' + filter_id;
    } else {
      window.location.hash = new_hash;
    }
  }

  function removeHash(filter_type) {
    var new_hash = '';
    var hash_url = window.location.hash.substr(1);
    var hash_array = hash_url.split('/');

    for (var i = 0; i < hash_array.length; i++) {
      if (hash_array[i] == filter_type) {
        i++;
      } else {
        if (!(new_hash == '') && !(hash_array[i] == '')) {
          new_hash += '/' + hash_array[i];
        } else {
          new_hash += hash_array[i];
        }
      }
    }
    window.location.hash = new_hash;
  }

  function getHash(filter_type) {
    var hash_url = window.location.hash.substr(1);
    var hash_array = hash_url.split('/');

    for (var i = 0; i < hash_array.length; i++) {
      if (hash_array[i] == filter_type) {
        return hash_array[++i];
      }
    }
    return false;
  }
