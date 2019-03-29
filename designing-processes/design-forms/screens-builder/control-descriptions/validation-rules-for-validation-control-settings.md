---
description: Use these rules to describe how to validate your ProcessMaker Screen controls.
---

# Validation Rules for "Validation" Control Settings

## Overview

Use the following rules to validate your [ProcessMaker Screen controls](./). If a control that has a **Validation** setting does not contain any value or properly structured validation rule, that control automatically passes validation.

{% hint style="info" %}
If you want a validation to fail for undefined or `''`, use the [required](validation-rules-for-validation-control-settings.md#required) rule.
{% endhint %}

## Validation Rules

### **accepted**

The field under validation must be `yes`, `on`, `1` or true. This is useful for validating "Terms of Service" acceptance.

### **after:date**

The field under validation must be after the given date.

### **after\_or\_equal:date**

The field under validation must be after or equal to the given field.

### **alpha**

The field under validation must be entirely alphabetic characters.

### **alpha\_dash**

The field under validation may have alphanumeric characters as well as dashes and underscores.

### **alpha\_num**

The field under validation must contain entirely alphanumeric characters.

### **array**

The field under validation must be an array.

### **before:date**

The field under validation must be before the given date.

### **before\_or\_equal:date**

The field under validation must be before or equal to the given date.

### **between:min,max**

The field under validation must have a size between the given `min` and `max`. [Strings](validation-rules-for-validation-control-settings.md#string), [numerics](validation-rules-for-validation-control-settings.md#numeric), and files are evaluated in the same fashion as the size rule.

### **Boolean**

The field under validation must be a Boolean value of the form `true`, `false`, `0`, `1`, `'true'`, `'false'`, `'0'`, `'1'`,

### **confirmed**

The field under validation must have a matching field of `foo_confirmation`. For example, if the field under validation is password, a matching `password_confirmation` field must be present in the input.

### **date**

The field under validation must be a valid date format which is acceptable by Javascript's `Date` object.

### **digits:value**

The field under validation must be numeric and must have an exact length of value.

### **different:attribute**

The given field must be different than the field under validation.

### **email**

The field under validation must be formatted as an email address.

### **hex**

The field under validation should be a hexadecimal format. Useful in combination with other rules, like `hex|size:6` for hex color code validation.

### **in:foo,bar,...**

The field under validation must be included in the given list of values. The field can be an array or string.

### **integer**

The field under validation must have an integer value.

### **max:value**

Validate that an attribute is no greater than a given size.

{% hint style="info" %}
Maximum checks are inclusive.
{% endhint %}

### **min:value**

Validate that an attribute is at least a given size.

{% hint style="info" %}
Minimum checks are inclusive.
{% endhint %}

### **not\_in:foo,bar,...**

The field under validation must not be included in the given list of values.

### **numeric**

Validate that an attribute is numeric. The string representation of a number passes.

### **present**

The field under validation must be present in the input data but can be empty.

### **regex:pattern**

The field under validation must match the given regular expression.

### **required**

Checks if the length of the String representation of the value is complies with the validation described with the following `required` rules.

#### **required\_if:anotherfield,value**

The field under validation must be present and not empty if the `anotherfield` field is equal to any value.

#### **required\_unless:anotherfield,value**

The field under validation must be present and not empty unless the `anotherfield` field is equal to any value.

#### **required\_with:foo,bar,...**

The field under validation must be present and not empty only if any of the other specified fields are present.

#### **required\_with\_all:foo,bar,...**

The field under validation must be present and not empty only if all of the other specified fields are present.

#### **required\_without:foo,bar,...**

The field under validation must be present and not empty only when any of the other specified fields are not present.

#### **required\_without\_all:foo,bar,...**

The field under validation must be present and not empty only when all of the other specified fields are not present.

### **same:attribute**

The given field must match the field under validation.

### **size:value**

The field under validation must have a size matching the given value. For string data, value corresponds to the number of characters. For numeric data, value corresponds to a given integer value.

### **string**

The field under validation must be a string.

### **url**

Validate that an attribute has a valid URL format.

## Related Topics

{% page-ref page="../view-the-inspector-pane.md" %}

{% page-ref page="./" %}

{% page-ref page="line-input-control-settings.md" %}

{% page-ref page="select-control-settings.md" %}

