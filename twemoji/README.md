# Twitter Emoji (Twemoji)

The aim of this library is to provide a Unicode standard based way to implement [emoji](http://en.wikipedia.org/wiki/Emoji) across all platforms.


## API

Following all methods exposed through the `twemoji` namespace.


### twemoji.parse( ... )

This is the main parsing utility and it has 3 overloads per each parsing type.

There are mainly two kind of parsing, string parsing, and DOM parsing.

Each of them accept a callback to generate each image source, or an options object with parsing info.

Here a walk through all parsing possibilities.

##### string parsing
Given a generic string, it will replace all emoji with an `<img>` tag.

While this can be used to inject via `innerHTML` emoji image tags, please note that this method does not sanitize the string or prevent malicious code to be executed. As example, if the text contains a `<script>` tag, this **will not** be converted into `&lt;script&gt;` since it's out of this method scope to prevent these kind of attacks.

However, for already sanitized strings, this method can be considered safe enough (please see DOM parsing if security is one of your major concerns).

```js
twemoji.parse('I \u2764\uFE0F emoji!');

// will produce
/*
I <img
  class="emoji"
  draggable="false"
  alt="❤️"
  src="https://abs.twimg.com/emoji/v1/36x36/2764.png"> emoji!
*/
```

##### string parsing + callback
If a callback is passed, the `src` attribute will be the one returned by the same callback.
```js
twemoji.parse(
  'I \u2764\uFE0F emoji!',
  function(icon, options, variant) {
    return '/assets/' + options.size + '/' + icon + '.gif';
  }
);

// will produce
/*
I <img
  class="emoji"
  draggable="false"
  alt="❤️"
  src="/assets/36x36/2764.gif"> emoji!
*/
```

By default, the `options.size` parameter will be the string `"36x36"` and the `variant` will be an optional `\uFE0F` char that is usually ignored by default. If your assets include or distinguish between `\u2764\uFE0F` and `\u2764` you might want to use such variable.

##### string parsing + callback returning `falsy`
If the callback returns _falsy values_ such `null`, `undefined`, `0`, `false` or an empty string, nothing will change for that specific emoji.
```js
var i = 0;
twemoji.parse(
  'emoji, m\u2764\uFE0Fn am\u2764\uFE0Fur',
  function(icon, options, variant) {
    if (i++ === 0) {
      return; // no changes made first call
    }
    return '/assets/' + icon + options.ext;
  }
);

// will produce
/*
emoji, m❤️n am<img
  class="emoji"
  draggable="false"
  alt="❤️"
  src="/assets/2764.png">ur
*/
```

##### string parsing + object
In case an object is passed as second parameter, the passed `options` object will reflect its properties.
```js
twemoji.parse(
  'I \u2764\uFE0F emoji!',
  {
    callback: function(icon, options) {
      return '/assets/' + options.size + '/' + icon + '.gif';
    },
    size: 128
  }
);

// will produce
/*
I <img
  class="emoji"
  draggable="false"
  alt="❤️"
  src="/assets/128x128/2764.gif"> emoji!
*/
```


##### DOM parsing

Differently from `string` parsing, if the first argument is a `HTMLElement` generated image tags will be replacing emoji that are **inside `#text` node only** without compromising surrounding nodes, listeners, and avoiding completely the usage of `innerHTML`.

If security is a major concern, this parsing can be considered the safest option but with a slightly penalized performance gap due DOM operations that are inevitably *costy* compared to basic strings.

```js
var div = document.createElement('div');
div.textContent = 'I \u2764\uFE0F emoji!';
document.body.appendChild(div);

twemoji.parse(document.body);

var img = div.querySelector('img');

// note the div is preserved
img.parentNode === div; // true

img.src;        // https://abs.twimg.com/emoji/v1/36x36/2764.png
img.alt;        // \u2764\uFE0F
img.class;      // emoji
img.draggable;  // false

```

All other overloads described for `string` are available exactly same way for DOM parsing.

### Object as parameter
Here the list of properties accepted by the optional object that could be passed to parse.

```js
  {
    callback: Function,
    base: string,
    ext: string,
    size: string|number
  }
```

##### callback
The function to invoke in order to generate images `src`.

By default it is a function like the following one:
```js
function imageSourceGenrator(icon, options) {
  return ''.concat(
    options.base, // by default Twitter Inc. CDN
    options.size, // by default "36x36" string
    '/',
    icon,         // the found emoji as code point
    options.ext   // by default ".png"
  );
}
```


##### base
The default url to be used, by default it's the same as `twemoji.base` so if you modify the former, it will reflect as default for all parsed strings or nodes.


##### ext
The default image extension to be used, by default it's the same as `twemoji.ext` which is `".png"`.

If you modify the former, it will reflect as default for all parsed strings or nodes.


##### size
The default assets size to be used, by default it's the same as `twemoji.size` which is `"36x36"`.

If you modify the former, it will reflect as default for all parsed strings or nodes.
