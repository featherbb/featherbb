    	<div class="blockform">
    		<div class="box">
    			<form method="post" action="{form_action}">
    				<input type="hidden" name="topics" value="{topics}" />
    				<input name="delete_comply" value="1" type="hidden" />
    				<input type="hidden" name="csrf_token" value="{csrf_token}" />
    				<div class="inform">
    				<div class="forminfo">
    					<p>{delete_messages_comply}</p>
    				</div>
    			</div>
    			<p class="buttons"><input type="submit" name="delete" value="{delete}" /> <a href="javascript:history.go(-1)">{go_back}</a></p>
    		</form>
    	</div>
    </div>
    <div class="clearer"></div>
