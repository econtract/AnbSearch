# AnbSearch

## Search Form
The form can be rendered anywhere using below short-code.

```
[anb_search_form] (without parameters)
[anb_search_form cat=tv,gsm zip=3500 sg=business] (with parameters, this will the form with postal code 3500, and will select internet, tv and mobile from services)
```

Whereas the product type `internet` in the form that is going to be generated would always remain selected.

This short_code can be copied to anywhere in post, page or can be even utilized in code.

If you pass no parameters to this short_code and just write `[anb_search_form]`,
it'll render empty form expected from internet already selected :) So all the parameters are optional.

### List of keys for parameters which can be passed
```text
hidden_sp = Hidden Supplier (Can be used to hide supplier's drop down, instead of that it'll pass a single hidden supplier id to the API) 
      cat = Product Type
      zip = Postal Code/Installation Area
       sg = Segment/Type of Use
```

### Allowed values of the above keys

### For `cat`
```text
 internet = Internet
       tv = TV
telephone = Fixed Line
      gsm = Mobile 
```

### For `sg`
```text
consumer = Private
business = Business 
```

### For `zip`
Any 4 digit number.
