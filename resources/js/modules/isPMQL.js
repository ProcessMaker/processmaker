String.prototype.isPMQL = function () {
  return /^.+(?:[=><]|LIKE|NOT IN \[|IN \[).+$/i.test(this);
};
