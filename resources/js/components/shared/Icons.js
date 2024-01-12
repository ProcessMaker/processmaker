import icons from 'js-yaml-loader!@fortawesome/fontawesome-free/metadata/icons.yml';

let common = [
  'search',
  'search-plus',
  'search-dollar',
  'check-square',
  'list',
  'id-badge',
  'clipboard-list',
  'clipboard-check',
  'clipboard',
  'fire',
  'fire-alt',
  'star',
  'dollar-sign',
  'users',
  'user-circle',
  'trophy'
];

let exclusions = [
  'align-center',
  'align-justify',
  'align-left',
  'align-right',
  'arrows-alt',
  'arrows-alt-h',
  'arrows-alt-v',
  'bars',
  'beer',
  'bong',
  'border-all',
  'border-none',
  'border-style',
  'cannabis',
  'circle-notch',
  'cog',
  'cogs',
  'compress',
  'compress-alt',
  'compress-arrows-alt',
  'custom',
  'democrat',
  'disease',
  'download',
  'ellipsis-h',
  'ellipsis-v',
  'expand',
  'expand-alt',
  'expand-arrows-alt',
  'faucet',
  'file-download',
  'file-export',
  'file-import',
  'file-upload',
  'flag-usa',
  'font-awesome-logo-full',
  'grip-horizontal',
  'grip-lines',
  'grip-lines-vertical',
  'grip-vertical',
  'hand-holding-water',
  'hand-middle-finger',
  'hospital-user',
  'joint',
  'lungs',
  'republican',
  'upload',
];

export default class
{
  static list() {
    let list = [];
    
    common.forEach(icon => {
      if (icons[icon]) {
        list.push(this.parseIcon(icon, icons[icon]));
      }
    });
    
    for (let [value, icon] of Object.entries(icons)) {
      if (this.shouldIncludeIcon(value, icon)) {
        list.push(this.parseIcon(value, icon));
      }
    }
    
    return list;
  }

  static grouped() {
    let list = [
      { group: 'Common', icons: [] },
      { group: 'All Icons', icons: [] },
    ];
    
    for (let [value, icon] of Object.entries(icons)) {
      if (! exclusions.includes(value)) {
        if (common.includes(value)) {
          list[0].icons.push(this.parseIcon(value, icon));
        }
        
        if (this.shouldIncludeIcon(value, icon)) {
          list[1].icons.push(this.parseIcon(value, icon));
        }
      }
    }
    
    return list;
  }
  
  static shouldIncludeIcon(value, icon) {
    let should = false;
    
    let shouldNotContain = ['-left', '-right', '-up', '-down'];

    if (! exclusions.includes(value) && ! common.includes(value)) {
      if (icon.styles.includes('solid') && ! icon.changes.includes('5.13.0')) {
        should = true;
      }
    }
    
    shouldNotContain.forEach(trigger => {
      if (value.includes(trigger)) {
        should = false;
      }
    });
    
    return should;
  }
  
  static parseIcon(value, icon) {
    let object = {
      value: value,
      label: icon.label,
      search: icon.search.terms.join()
        + ','
        + value
        + ','
        + icon.label.toLowerCase(),
    };
    
    if (object.label.startsLowerCase()) {
      object.label = object.label.unSlug().titleCase();
    }
            
    return object;
  }  
}

String.prototype.startsLowerCase = function() {
  let firstLetter = this.substring(0,1);
  return firstLetter == firstLetter.toLowerCase();
};

String.prototype.unSlug = function() {
  return this.split('-').join(' ');
};

String.prototype.titleCase = function() {
  let words = this.split(' ');
  words = words.map(word => {
    return word.substring(0,1).toUpperCase() + word.substring(1);
  });
  return words.join(' ');
};