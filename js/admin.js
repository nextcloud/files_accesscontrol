/**
 * @copyright Copyright (c) 2016 Morris Jobke <hey@morrisjobke.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

(function() {
	OCA.FilesAccessControl = OCA.FilesAccessControl || {};

	/**
	 * @class OCA.FilesAccessControl.Operation
	 */
	OCA.FilesAccessControl.Operation =
		OCA.WorkflowEngine.Operation.extend({
			defaults: {
				'class': 'OCA\\FilesAccessControl\\Operation',
				'name': '',
				'checks': [],
				'operation': 'deny'
			}
		});

	/**
	 * @class OCA.FilesAccessControl.OperationsCollection
	 *
	 * collection for all configurated operations
	 */
	OCA.FilesAccessControl.OperationsCollection =
		OCA.WorkflowEngine.OperationsCollection.extend({
			model: OCA.FilesAccessControl.Operation
		});

	/**
	 * @class OCA.FilesAccessControl.OperationView
	 *
	 * this creates the view for a single operation
	 */
	OCA.FilesAccessControl.OperationView =
		OCA.WorkflowEngine.OperationView.extend({
			model: OCA.FilesAccessControl.Operation,
			render: function() {
				var $el = OCA.WorkflowEngine.OperationView.prototype.render.apply(this);
				$el.find('input.operation-operation').addClass('hidden');
			}
		});

	/**
	 * @class OCA.FilesAccessControl.OperationsView
	 *
	 * this creates the view for configured operations
	 */
	OCA.FilesAccessControl.OperationsView =
		OCA.WorkflowEngine.OperationsView.extend({
			initialize: function() {
				OCA.WorkflowEngine.OperationsView.prototype.initialize.apply(this, [
					'OCA\\FilesAccessControl\\Operation'
				]);
			},
			renderOperation: function(operation) {
				var subView = new OCA.FilesAccessControl.OperationView({
						model: operation
					});

				OCA.WorkflowEngine.OperationsView.prototype.renderOperation.apply(this, [
					subView
				]);
			}
		});
})();


$(document).ready(function() {
	OC.SystemTags.collection.fetch({
		success: function() {
			new OCA.FilesAccessControl.OperationsView({
				el: '#files_accesscontrol .rules',
				collection: new OCA.FilesAccessControl.OperationsCollection()
			});
		}
	});
});
