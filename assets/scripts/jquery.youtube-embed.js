// Generated by CoffeeScript 1.5.0
(function() {
  var $;

  $ = this.jQuery;

  $(function() {
    var resize;
    resize = function() {
      return $('iframe.youtube.embed').each(function() {
        var $embed, aspect_ratio, data, height;
        $embed = $(this);
        if ($embed.attr('height')) {
          return;
        }
        data = $embed.data('youtube');
        aspect_ratio = data ? data.aspect_ratio : 'widescreen';
        switch (aspect_ratio) {
          case 'widescreen':
            height = ($embed.width() / 16) * 9;
        }
        if (height) {
          return $embed.height(height);
        }
      });
    };
    resize();
    return $(window).resize(resize);
  });

}).call(this);
