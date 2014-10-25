'use strict';

/**
 * @ngdoc function
 * @name communityActionApp.controller:AboutCtrl
 * @description
 * # AboutCtrl
 * Controller of the communityActionApp
 */
angular.module('communityActionApp')
  .controller('AboutCtrl', function ($scope) {
    $scope.awesomeThings = [ //In here to show how tests work for now EAW
      'Beloit',
      'College',
      'Computers'
    ];
  });
