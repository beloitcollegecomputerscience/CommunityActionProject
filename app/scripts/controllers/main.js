'use strict';

/**
 * @ngdoc function
 * @name communityActionApp.controller:MainCtrl
 * @description
 * # MainCtrl
 * Controller of the communityActionApp
 */
angular.module('communityActionApp')
  .controller('MainCtrl', function ($scope) {
    $scope.awesomeThings = [ //In here to show how tests work for now EAW
      'Beloit',
      'College',
      'Computers'
    ];
  });
