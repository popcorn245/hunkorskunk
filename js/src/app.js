var	app = angular.module("app",["ngRoute","ui.bootstrap"])
		.factory('db', function($http, $q){
			var db = {};
				db.get = function(name){
					var deferred = $q.defer();
					$http.post('./api/',{action:"read", view:name})
					.success(function(data, status, headers, config) {
						deferred.resolve(data.data);
					})
					.error(function(data, status, headers, config) {
						alert("Failed to Get Data!");
					});
					return deferred.promise;
				};
				db.rate = function(id, rate, com){
					$http.post('./api/',{action:"rate", guy:id, stars:rate, comment:com})
						.success(function(data, status, headers, config) {
							if(data.status == 'success'){
								console.log(data.data);
							}else{
								alert(data.data);
							}
						})
						.error(function(data, status, headers, config) {
							alert("Failed to Send Data!");
						});
				};
			return db;
		})
		.controller('homeCtrl', function($scope, db, $filter){
			$scope.rate    = 0;
			$scope.comment = '';
			$scope.rating_count = 0;
			$scope.oldBorder = $("#comment").css("border");
			$scope.user = document.cookie.split("=")[1];

			$scope.closeIt = function(){
				$(".alert").slideUp();
			};

			$scope.rateHover = function(value) {
			    $scope.overStar = value;
			};

			$scope.rateIt = function(){
				if(!$scope.stopIt()){
					$(".home").css("padding-bottom","115px");
					$(".footer .comment").slideDown();
					var newAvg = 0;
					$.each($scope.guys[$scope.guy].ratings, function(i){
						newAvg += parseInt($scope.guys[$scope.guy].ratings[i].stars);
					});
					newAvg += parseInt($scope.rate);
					$scope.guy_stars = Math.round(newAvg / ($scope.rating_count + 1));
				}
			};

			$scope.guyIt = function(){
				$scope.guy_id       = $scope.guys[$scope.guy].guy;
				$scope.guy_name     = $scope.guys[$scope.guy].name;
				$scope.guy_picture  = $scope.guys[$scope.guy].picture;
				$scope.guy_stars    = $scope.guys[$scope.guy].stars;
				$scope.ratings      = $scope.guys[$scope.guy].ratings;
				if($scope.guys[$scope.guy].ratings){
					$scope.rating_count = $scope.guys[$scope.guy].ratings.length;
				}else{
					$scope.rating_count = 0;
				}
				$(".footer .comment").slideUp();
			};

			$scope.stopIt = function(){
				var stop = false;
				$.each($scope.ratings, function(i){
					if($scope.ratings[i].user == $scope.user){
						stop = true;
					}
				});

				if(stop){
					$scope.hideRate = true;
				}

				return stop;
			};

			$scope.submitIt = function(){
				if(!$scope.stopIt()){
					if($("#comment")[0].checkValidity()){
						var timestamp = new Date();
						var newRating = {
							"comment":$scope.comment,
							"stars":$scope.rate,
							"time":$filter('date')(timestamp,"M/d/yyyy h:mm:ss a"),
							"user":$scope.user
						}
						if(!$scope.ratings){
							$scope.ratings = [];
						}
						$scope.ratings.push(newRating);
						db.rate($scope.guy_id, $scope.rate, $scope.comment);
						$scope.rate = 0;
						$scope.comment = '';
						$scope.rating_count++;
						$("#comment").css("border",$scope.oldBorder);
						if($scope.guy < 4){
							$scope.guy++;
							$scope.guyIt();
						}else{
							$scope.hideRate = true;
						}
					}else{
						$("#comment").css("border","2px solid red");
					}
				}
			};

			var prom = db.get("guys");
			prom.then(function(res){
				$scope.guys = res;
				$scope.guyIt();
			});
		})
		.config(['$routeProvider',
	  		function($routeProvider) {
		    	$routeProvider
			    	.when('/home', {
				        templateUrl: 'html/home.html',
				        controller: 'homeCtrl'
				    })
				    .otherwise({
				        redirectTo: '/home'
				    });
	  		}
		]);