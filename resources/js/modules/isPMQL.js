String.prototype.isPMQL = function() {
  return /^.+(?:[=><]|LIKE).+$/i.test(this);
};
