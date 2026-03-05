$(document).ready(function() {
  function filterOptions(inputId, selectId) {
    var filter = $(inputId).val().toLowerCase();
    var hasMatch = false;
    $(selectId + ' option').each(function() {
      var text = $(this).text().toLowerCase();
      var val = $(this).val();
      if (val === '') return;
      if (text.indexOf(filter) > -1) {
        $(this).show();
        if (!hasMatch) {
            $(this).prop('selected', true);
            hasMatch = true;
        }
      } else {
        $(this).hide();
      }
    });
    if (!hasMatch) {
       $(selectId).val('');
    }
  }

  $('#course_search').on('keyup', function() {
    filterOptions('#course_search', '#course_id');
  });

  $('#bulk_course_search').on('keyup', function() {
    filterOptions('#bulk_course_search', '#bulk_course_id');
  });

  bsCustomFileInput.init();
});