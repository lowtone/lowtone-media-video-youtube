angular = @angular

@YouTubeSearchCtrl = ($scope, $http) ->

	delete $http.defaults.headers.common['X-Requested-With'] # Fix cross domain

	$scope.video_url = ""
	$scope.search_query = ""

	$scope.results = []

	$scope.search = ->
		http_options = 
			method: 'GET'
			url: 'https://gdata.youtube.com/feeds/api/videos'
			params:
				alt: 'json'
				q: $scope.search_query
				v: 2
				'max-results': 28

		success = (response) ->
			console.dir response
			$scope.results = response.feed.entry
		
		$http(http_options).success(success)

	$scope.select = (item) ->
		$scope.video_url = item.link[0].href

		if($scope.selected)
			$scope.selected.class = null

		$scope.selected = item
		$scope.selected.class = 'selected'

@YouTubeSearchCtrl.$inject = ['$scope', '$http']