# TYPO3.Asset
First Draft to use Assetic for Asset Management

## Requirements:
- Assetic: https://github.com/kriswallsmith/assetic
  - Symfony.Component.Process: https://github.com/symfony/Process

We suggest using [composer](https://getcomposer.org/) for installing these dependencies.


## Examples:
Demo Package: https://github.com/mneuhaus/Demo.Asset

```yaml
Assets:
  Bundles:
    Js:
      'Demo.Asset:jQuery':
        Files:
          - resource://Demo.Asset/Public/js/jquery.js

      'Demo.Asset:Main':
        Dependencies: ['Demo.Asset:jQuery']
        Files:
          - resource://Demo.Asset/Public/js/main.js

      'Demo.Asset:Require':
        Files:
          - resource://Demo.Asset/Public/js/require.js

    CSS:      
      'Demo.Asset:LessStyles':
        Files:
          - resource://Demo.Asset/Public/less/variables.less
          - resource://Demo.Asset/Public/less/main.less
        Filters:
          Assetic\Filter\LessphpFilter: []
        PreCompileMerge: true

      'Demo.Asset:CssStyles':
        Files:
          - resource://Demo.Asset/Public/css/main.css

      'Demo.Asset:ComplexStyles':
        Files:
          - resource://Demo.Asset/Public/css/bad.css
        Alterations:
          resource://Demo.Asset/Public/css/bad.css: 
            After: 
              - resource://Demo.Asset/Public/css/good.css
            Before: 
              - resource://Demo.Asset/Public/css/good.css
            Replace: 
              - resource://Demo.Asset/Public/css/good.css

      'Demo.Asset:DependencyStyles':
        Dependencies: ['Demo.Asset:LessStyles']
        Files:
          - resource://Demo.Asset/Public/less/dependency.less
```

```html
<as:bundle.css name="Demo.Asset:LessStyles" />
<as:bundle.js name="Demo.Asset:Main" />
<as:requireJs name="Demo.Asset:Require" />
<as:requireJs flush="true" />
```