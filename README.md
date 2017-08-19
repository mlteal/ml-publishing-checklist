# ML Publishing Checklist

A publishing checklist for post editing that enforces the 
completion of specific post elements before publishing via javascript.

#### Enabling or Disabling Specific Checklist Items

In future versions, there will be a single site option available via the 
"Publishing Checklist" section in the Writing options page. By default, all 
checklist items are active.

#### Adding Or Modifying A Checklist Item 

Checklist items can be added or modified via the `ml_registered_checklist_items` filter. 

The array passed via the contains the key => attributes array for each item. This format must 
be maintained and returned for the checklist items to be found and available. 
