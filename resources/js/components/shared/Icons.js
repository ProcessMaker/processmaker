const icons = [
  "Blueprint",
  "Boy On The Rocket",
  "Car Sale",
  "Cash and Credit Card",
  "Cash Receipt",
  "Certification",
  "Classroom",
  "Coworking",
  "Create Order",
  "Crowd",
  "CV",
  "Emergency Exit",
  "Exam",
  "Exclamation Mark",
  "Factory",
  "Form",
  "Graduate",
  "Graduation Cap",
  "Group Of Companies",
  "Hotel Bed",
  "Leave",
  "Mortgage",
  "Online Maintenance Portal",
  "Pass Fail",
  "Permanent Job",
  "Phone Message",
  "Protect",
  "Season Sale",
  "Student Center",
  "Tax",
  "Test Passed",
  "Vial Virus",
  "Web Advertising",
];

export default class {
  static list() {
    const list = [];
    icons.forEach((icon) => {
      list.push({
        label: icon,
        value: icon,
      });
    });
    return list;
  }
}
