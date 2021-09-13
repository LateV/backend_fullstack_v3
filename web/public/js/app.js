const STATUS_SUCCESS = 'success';
const STATUS_ERROR = 'error';

var comments_tree_node = {
	data: function ()
	{
		return {
			commentText: ""
			
		};
	},
	props: {
		comments_tree_data: Object
	},
	template: '<li class="node-tree">  <span class="label">{{ comments_tree_data.user.personaname + \' - \'}}</span>  <small class="text-muted">{{comments_tree_data.text}}</small>  <a role="button" @click="likeComment(comments_tree_data.id)">    <svg class="bi bi-heart-fill" width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">      <path fill-rule="evenodd" d="M8 1.314C12.438-3.248 23.534 4.735 8 15-7.534 4.736 3.562-3.248 8 1.314z" clip-rule="evenodd"/>    </svg>{{ comments_tree_data.likes }} </a> {{ comments_tree_data.time_created }} <a role="button" @click="reply(comments_tree_data.id)">reply</a> <br>  <div style="display: none;" class="addRelpyComment" :id="\'reply_comment_\' + comments_tree_data.id">    <form class="form-inline">      <div class="form-group">        <input type="text" class="form-control" :id="\'reply_comment_field\' + comments_tree_data.id" v-model="commentText">      </div>      <button type="button" class="btn btn-primary" @click="addCommentReply(comments_tree_data.id, comments_tree_data.post_id)">Add comment</button>    </form>  </div>  <ul v-if="comments_tree_data.children && comments_tree_data.children.length">    <comments_tree_node v-for="child_comment in comments_tree_data.children" :comments_tree_data="child_comment"></comments_tree_node></ul></li>',
	name: "comments_tree_node",
	methods: {
		reply: function (reply_id)
		{
			var self = this;
			if (!self.$root.user.id)
			{
				alert('please, authorize');
				return;
			}
			
			$( 'div[id^="reply_comment_"]' ).each( function ()
			{
				id = this.id
				if (  id != '#reply_comment_' + reply_id )
				{
					$( '#' + this.id ).hide();
				}
				
			} );
			$( 'input[id^="reply_comment_field"]' ).each( function ()
			{
				id = this.id;
				$( '#' + this.id ).val('');
			} );
			$( '#reply_comment_' + reply_id ).show();
		},
		likeComment: function (id) {
			var self = this;
			if (!self.$root.user.id)
			{
				alert('please, authorize');
				return;
			}
			const url = '/main_page/like_comment/' + id;
			axios
				.get( url )
				.catch( function ( error )
				{
					if ( error.response.data.need_auth )
					{
						alert( 'Please, authorize' );
						return;
					} else if ( error.response.data.not_enough_likes )
					{
						alert( 'Not enough lakes' );
						return;
						alert();
					} else
					{
						alert('Unexpected error');
						return;
					}
				})
				.then( function ( response )
				{
					console.log(self);
					console.log( response );

					self.comments_tree_data.likes = response.data.comment.likes;
					self.$root.user.likes_balance = response.data.user.likes_balance;
				})

		},
		addCommentReply: function (reply_id, post_id)
		{
			var self = this;
			if (!self.$root.user.id)
			{
				alert('please, authorize');
				return;
			}
			
			if(self.commentText) {

				var comment = new FormData();
				comment.append('reply_id', reply_id);
				comment.append('post_id', post_id);
				comment.append('text', self.commentText);	
				axios.post(
					'/main_page/comment',
					comment
				)
				.catch( function ( error )
				{
					console.log( error );
					alert( 'Unexpected error' );
					return;
				} )
				.then( function ( response )
				{
					console.log(response)
					if (response.data.post)
					{
						self.$root.post = response.data.post;
					}
					self.commentText = '';
					$( '#reply_comment_' + reply_id ).hide();
				});
			}
		}
	}
}

var comments_tree = {
	props: {
		comments_tree_data: Object
	},
	template: '<div class="tree"><ul class="tree-list"><comments_tree_node :comments_tree_data="comments_tree_data"></comments_tree_node></ul></div>',
	components: {
		comments_tree_node
	}
}


var comments = {
	props: {
		comment: Object
	},
	template: '<div><comments_tree :comments_tree_data="comment"></comments_tree></div>',
	components: {
		comments_tree
	}
}
  

var app = new Vue({
	el: '#app',
	data: {
		user: '',
		login: '',
		pass: '',
		boosterpack: false,
		post: false,
		invalidLogin: false,
		invalidPass: false,
		invalidSum: false,
		posts: [],
		addSum: 0,
		amount: 0,
		likes: 0,
		commentText: '',
		boosterpacks: [],
	},
	components: {
		'comments': comments
	},
	computed: {
		test: function () {
			var data = [];
			return data;
		}
	},
	created(){
		var self = this
		console.log( this );
		axios
			.get('/main_page/get_all_posts')
			.then(function (response) {
				self.posts = response.data.posts;
			})

		axios
			.get('/main_page/get_boosterpacks')
			.then(function (response) {
				self.boosterpacks = response.data.boosterpacks;
			} )
		axios
			.get('/main_page/get_current_user')
			.then(function (response) {
				self.user = response.data.user;
			})
	},
	methods: {
		logout: function () {
			axios.post( '/main_page/logout' )
				.catch( function ( error )
				{
					alert('Unexpected error');
					return;
				})
				.then(function (response) {
					location.reload();
				})
		},
		logIn: function () {
			var self = this;
			self.likes = 0;
			if(self.login === ''){
				self.invalidLogin = true
			}
			else if(self.pass === ''){
				self.invalidLogin = false
				self.invalidPass = true
			}
			else{
				self.invalidLogin = false
				self.invalidPass = false

				form = new FormData();
				form.append("login", self.login);
				form.append("password", self.pass);

				axios.post('/main_page/login', form)
					.catch( function ( error )
					{
						if ( error.response.data.incorrect_login_or_password )
						{
							self.invalidPass = true
							self.invalidLogin = true
							alert( 'Wrong login or password' );
							return;
						} else
						{
							alert('Unexpected error');
							return;
						}
						return;
					})
					.then(function (response) {
						if (!response) { 
							return;
						}
						if(response.data.user) {
							location.reload();
						}
					})
			}
		},
		addComment: function(id) {
			var self = this;

			if (!self.user.id)
			{
				alert('please, authorize');
				return;
			}
			if(self.commentText) {

				var comment = new FormData();
				comment.append('post_id', id);
				comment.append('text', self.commentText);

				axios.post(
					'/main_page/comment',
					comment
				)
				.catch( function ( error )
				{
						if ( error.response.data.need_auth )
						{
							alert( 'Please, authorize' );
							return;
						} else if ( error.response.data.wrong_input_text )
						{
							alert( 'Wrong input text' );
							return;
							alert();
						} else
						{
							alert('Unexpected error');
							return;
						}
				})
				.then( function ( response )
				{
					self.post = response.data.post;
					$( 'input[id^="addComment"]' ).val('');
				});
			}

		},
		refill: function () {
			var self = this;

			if ( !self.user.id )
			{
				alert('please, authorize');
				return;
			}
			
			if(self.addSum === 0){
				self.invalidSum = true
			}
			else{
				self.invalidSum = false
				sum = new FormData();
				sum.append('sum', self.addSum);
				axios.post( '/main_page/add_money', sum )
					.catch( function ( error )
					{
						if ( error.response.data.need_auth )
						{
							alert( 'Please, authorize' );
							return;
						} else if ( error.response.data.bad_amount_of_money )
						{
							alert( 'Bad amount of money' );
							return;
							alert();
						} else
						{
							alert('Unexpected error');
							return;
						}
					})
					.then(function (response) {

						console.log(response);
						self.user.wallet_balance = response.data.user.wallet_balance;
						setTimeout(function () {
							$('#addModal').modal('hide');
						}, 500);
					})
			}
		},
		openPost: function (id) {
			var self = this;
			self.post = null;
			axios
				.get( '/main_page/get_post/' + id )
				.catch( function ( error )
				{
					if ( error.response.data.need_auth )
					{
						alert( 'Please, authorize' );
						return;
					} else
					{
						alert('Unexpected error');
						return;
					}
				})
				.then(function (response) {
					$('#postModal').modal('show');
					self.post = response.data.post;
					self.likes = self.post.likes;
					console.log(response);
					if(self.post){
						setTimeout(function () {
							$('#postModal').modal('show');
						}, 500);
					}
				})
		},
		addLike: function (type, id) {
			var self = this;
			if (!self.user.id)
			{
				alert( 'please, authorize' );
				return;
			}
			
			const url = '/main_page/like_' + type + '/' + id;
			axios
				.get( url )
				.catch( function ( error )
				{
					if ( error.response.data.need_auth )
					{
						alert( 'Please, authorize' );
						return;
					} else if ( error.response.data.not_enough_likes )
					{
						alert( 'Not enough lakes' );
						return;
						alert();
					} else
					{
						alert('Unexpected error');
						return;
					}
				})
				.then( function ( response )
				{
					

					self.user.likes_balance = response.data.user.likes_balance;
					self.likes = response.data.post.likes;
					self.post.likes = response.data.post.likes;
				})

		},
		buyPack: function (id) {
			var self = this;
			
			if (!self.user.id)
			{
				alert('please, authorize');
				return;
			}
			var pack = new FormData();
			pack.append('id', id);
			axios.post( '/main_page/buy_boosterpack', pack )
				.catch( function ( error )
				{
					if ( error.response.data.need_auth )
					{
						alert( 'Please, authorize' );
						return;
					} else if ( error.response.data.not_enough_wallets )
					{
						alert( 'Not enough wallets' );
						return;
						alert();
					} else
					{
						alert('Unexpected error');
						return;
					}
				})
				.then( function ( response )
				{
					self.amount = response.data.amount
					if(self.amount !== 0){
						self.user.likes_balance = null;
						self.user.wallet_balance = null;
						setTimeout( function ()
						{
							self.user.likes_balance = response.data.user.likes_balance;
							self.user.wallet_balance = response.data.user.wallet_balance;
							self.amount = response.data.amount
							$('#amountModal').modal('show');
						}, 500);
					}
				})
		}
	}
});

