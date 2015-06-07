/**
 * STF2015 Conent provider and content_form_renderer
 *
 * @author: M. Käser
 * @date: 23.12.2014
 **/
(function($) {
  var filter = {
    init : function() {
      var self = this;
      $('.stf_resp_wrapper').each(function() {
        var config = JSON.parse(decodeURIComponent($(this).attr('data-overlay')));
        $(this).find('.stf_resp_elem tbody tr').off('click').on('click', function() {
          var idx = $(this).attr('data-idx');
          var data = config.data[idx];
          var cols = config.col_config;
          self.showDataOverlay(config.content_type, config.heading, config.subheading, data, cols);
        });
      });
    },

    showDataOverlay : function(content_type, heading, subheading, data, cols) {
      var $overlay = this.getDataOverlay(content_type, heading, subheading, cols.names, cols.headings);
      for(var k in cols.names) {
        var name = cols.names[k];
        $overlay.find('.idx-' + k).html(data[name]);
      }
      $('html,body').css('overflow', 'hidden');
      $overlay.fadeIn();
    },

    hideDataOverlay : function() {
      this.getDataOverlay().fadeOut();
      $('html,body').css('overflow', 'auto');
    },

    getDataOverlay : function(content_type, heading, subheading, cols, headings) {
      if($("#stf_data_overlay .stf-da-content_type-" + content_type).length)
        return $("#stf_data_overlay .stf-da-content_type-" + content_type);

      $("#stf_data_overlay").remove();
      var self = this;
      var rows = '';
      var row = '';
      var overlay;
      for(var k in cols) {
        row = "";
        row += '<div class="stf_da_row type-' + cols[k] + '">';
        row += '<span class="stf_da_label">' + headings[k] + '</span>';
        row += '<span class="stf_da_data idx-' + k  + '">-</span>';
        row += '</div>'
        rows += row;
      }
      overlay =  '<div id="stf_data_overlay" class="stf-da-content_type-' + content_type + '">';
      overlay += '<div class="stf_da_overlay_wrapper">';
      overlay += '<div class="stf_da_close">X</div>';
      overlay += '<h5>' + heading + '</h5>';
      overlay += '<h6>' + subheading + '</h6>';
      overlay += rows;
      overlay += '<div class="stf_da_close btn-close">Schliessen</div>';
      overlay += '</div>';
      overlay += '</div>';
      overlay = $(overlay);
      overlay.appendTo('body').hide();
      overlay.find('.stf_da_close').off('click').on('click', function() {
        self.hideDataOverlay();
      })
      return overlay;
    }

  };

  filter.init();
})(jQuery);