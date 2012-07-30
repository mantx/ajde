;
if (typeof Application !== "undefined") {
	alert('Wrong loading order of javascript application, please see a website mechanic');
}

Application = function() {
	
	var stack = [];
	
	return {
		
		addBootstrap: function(fn) {
			stack.push(fn);
		},
		
		bootstrap: function() {
			for(var i in stack) {
				stack[i].call();
			}
		}
	}
	
}();