$ = @jQuery

$ ->
	resize = ->
		$('iframe.youtube.embed').each ->
			$embed = $ this

			return if $embed.attr('height')

			data = $embed.data('youtube')
			
			aspect_ratio = if data then data.aspect_ratio else 'widescreen'
			
			switch aspect_ratio
				when 'widescreen'
					height = ($embed.width() / 16) * 9

			$embed.height height if height

	resize()

	$(window).resize resize