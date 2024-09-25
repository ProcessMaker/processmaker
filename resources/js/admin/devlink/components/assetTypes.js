export default [
  {
    type: 'processes',
    name: 'Processes',
    url: '/processes',
    class: 'ProcessMaker\\Models\\Process',
    icon: 'fp-play-outline',
    nameField: 'name',
  },
  {
    type: 'screens',
    name: 'Screens',
    url: '/screens',
    class: 'ProcessMaker\\Models\\Screen',
    icon: 'fp-screen-outline',
    nameField: 'title',
  },
  {
    type: 'scripts',
    name: 'Scripts',
    url: '/scripts',
    class: 'ProcessMaker\\Models\\Script',
    icon: 'fp-script-outline',
    nameField: 'title',
  },
];