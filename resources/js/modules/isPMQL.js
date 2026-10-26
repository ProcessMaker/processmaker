function isPMQL() {
  return /^.+(?:[=><]|LIKE|NOT IN \[|IN \[).+$/i.test(this);
}

String.prototype.isPMQL = isPMQL;

export default isPMQL;
