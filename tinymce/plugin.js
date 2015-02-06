// Based on the tinyMce plugin "wordpress"
tinymce.PluginManager.add( 'emoji', function( editor, url ) { 
	var TAG_NAME = "emoji";

	var previousContent = '',
		isEmojifying = false;

	// Loads stylesheet for custom styles within the editor
	editor.on( 'init', function() {
		cssId = editor.dom.uniqueId();
		linkElm = editor.dom.create('link', {
			id: cssId,
			rel: 'stylesheet',
			href: url + '/css/editor.css'
		});
		editor.getDoc().getElementsByTagName('head')[0].appendChild(linkElm);
	} );

	// Hook into events to trigger emoji replace
	// keyup and change events handle as things are typed/inserted
	editor.on( 'keyup change', function( e ) {
		maybeEmojify();
	} );
	// SetContent handles paste
	editor.on( 'SetContent', function( e ) {
		emojify();
	} );

	var didContentChange = function() {
		return previousContent !== editor.getContent();
	}

	var maybeEmojify = function() {
		if ( didContentChange() ) {
			emojify();
		}
	}

	var updateContent = function() {
		previousContent = editor.getContent();
	}

	var emojify = function() {
		if ( isEmojifying ) {
			return;
		}

		if ( ! twemoji.test( editor.getContent() ) ) {
			return;
		}

		isEmojifying = true;

		var node = editor.getDoc();

		WPEmoji.parse( node );

		var imgs = editor.dom.select( 'img.emoji', node );
		tinymce.each( imgs, function( elem ) {
			// We don't want the emoji image to be selectable, so flag it as a placeholder
			elem.setAttribute( "data-mce-resize", "false" );
			elem.setAttribute( "data-mce-placeholder", "1" );
			// Additional data-attribute used to differentiate from other images
			elem.setAttribute( "data-emoji", elem.alt );
		} );

		updateContent();

		isEmojifying = false;
	}

	// Ensures that we swap back our placeholders with the actual emoji characters
	// Useful on save and when switching between HTML and text mode
	editor.on( 'PostProcess', function( event ) {
		if ( event.content ) {
			event.content = event.content.replace(/<img[^>]+>/g, function( image ) {
				if ( -1 !== image.indexOf( 'data-emoji' ) ) {
					var match = image.match( /data-emoji="([^"]+)"/ );
					if ( match ) {
						return match[1];
					} else {
						return '';
					}
				}
				return image;
			});
		}
	} );

	// Display the tag name instead of "img" in element path
	editor.on( 'ResolveName', function( event ) {
		var attr;
		if ( event.target.nodeName === 'IMG' && editor.dom.getAttrib( event.target, 'data-emoji' ) ) {
			event.name = TAG_NAME;
		}
	});
} );
